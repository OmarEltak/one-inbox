<?php

declare(strict_types=1);

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Page;
use App\Services\Email\CampaignDispatcher;
use App\Services\Email\ContactImporter;
use App\Services\Email\SpreadsheetParser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Multi-step wizard: upload → map → compose → review → launched.
 * Each step validates and advances `step`. Files live in storage
 * under "imports/{team_id}/..." until import completes.
 */
class EmailWizard extends Component
{
    use WithFileUploads;

    // Phase 3b (docs/OT1_LIMITS.md §11) adds the 'importing' step between
    // 'map' and 'compose' — async ImportEmailRecipients job runs the parse,
    // the wizard polls checkImportProgress() every 2s and auto-advances.
    public string $step = 'upload'; // upload | map | importing | compose | review | launched

    public $file = null; // uploaded TemporaryUploadedFile

    public ?string $storedPath = null;
    public ?string $originalName = null;
    public ?string $extension = null;

    /** @var array<int, string> */
    public array $detectedHeaders = [];

    /** @var array<int, array<string, string>> */
    public array $previewRows = [];

    // Column mapping
    public string $emailColumn = '';
    public string $nameColumn = '';
    /** @var array<int, string> */
    public array $customColumns = [];

    public ?int $importId = null;
    public ?string $importTag = null;
    public int $importedCount = 0;
    public int $skippedCount  = 0;
    public int $invalidCount  = 0;
    public int $totalRows     = 0;
    public string $importStatus = 'pending';
    public ?string $importError = null;

    // Compose
    public string $campaignName = '';
    public string $subject = '';
    public string $body = "Hi {{name}},\n\n";
    public bool $aiPersonalize = false;
    public ?int $senderPageId = null;
    public int $dailyCap = 200;
    public int $jitterMin = 30;
    public int $jitterMax = 60;

    public ?int $createdCampaignId = null;

    public function mount(): void
    {
        $team = Auth::user()?->currentTeam;
        if ($team) {
            $first = Page::where('team_id', $team->id)
                ->where('platform', 'email')
                ->where('is_active', true)
                ->first();
            $this->senderPageId = $first?->id;
        }
    }

    #[Computed]
    public function emailSenders()
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return collect();
        }
        return Page::where('team_id', $team->id)
            ->where('platform', 'email')
            ->where('is_active', true)
            ->get();
    }

    public function uploadAndPreview(): void
    {
        // Phase 3b shipped the async importer — cap lifted 2 MB → 10 MB.
        $this->validate([
            'file' => 'required|file|max:10240|mimes:csv,txt,xlsx',
        ], [
            'file.max' => __('File is too large. The current upload limit is 10 MB (~100,000 contacts).'),
        ]);

        $team = Auth::user()->currentTeam;
        $ext = strtolower($this->file->getClientOriginalExtension());
        if ($ext === 'txt') {
            $ext = 'csv';
        }

        $name = Str::random(24).'.'.$ext;
        $path = $this->file->storeAs("imports/{$team->id}", $name, 'local');

        $this->storedPath = $path;
        $this->originalName = $this->file->getClientOriginalName();
        $this->extension = $ext;

        $absolute = Storage::disk('local')->path($path);
        $parser = new SpreadsheetParser($absolute, $ext);
        $preview = $parser->preview(20);

        $this->detectedHeaders = $preview['headers'];
        $this->previewRows = $preview['rows'];

        // Best-effort column auto-detect.
        $this->emailColumn = $this->guessHeader(['email', 'e-mail', 'mail', 'email_address']);
        $this->nameColumn  = $this->guessHeader(['name', 'full_name', 'first_name', 'firstname']);

        $this->step = 'map';
    }

    private function guessHeader(array $candidates): string
    {
        foreach ($this->detectedHeaders as $h) {
            $normalized = strtolower(str_replace([' ', '-'], '_', $h));
            foreach ($candidates as $c) {
                if ($normalized === $c) {
                    return $h;
                }
            }
        }
        return '';
    }

    /**
     * Phase 3b (docs/OT1_LIMITS.md §11) — dispatches ImportEmailRecipients
     * async instead of parsing sync inside the Livewire request. Wizard
     * transitions to 'importing' and polls checkImportProgress() every 2s.
     */
    public function confirmMapAndImport(): void
    {
        $this->validate([
            'emailColumn' => 'required|string|in:'.implode(',', $this->detectedHeaders),
        ], [
            'emailColumn.required' => 'Pick which column contains the email address.',
            'emailColumn.in'       => 'Email column must match one of the detected headers.',
        ]);

        $team = Auth::user()->currentTeam;
        $tag  = 'imported:' . pathinfo($this->originalName ?? $this->storedPath, PATHINFO_FILENAME);

        $import = \App\Models\ContactImport::create([
            'team_id'       => $team->id,
            'user_id'       => Auth::id(),
            'filename'      => $this->storedPath,
            'original_name' => $this->originalName ?? basename($this->storedPath),
            'tag'           => $tag,
            'status'        => \App\Models\ContactImport::STATUS_PENDING,
        ]);

        \App\Jobs\ImportEmailRecipients::dispatch(
            $import->id,
            $this->storedPath,
            $this->extension,
            [
                'email'  => $this->emailColumn,
                'name'   => $this->nameColumn ?: null,
                'custom' => array_values(array_filter($this->customColumns)),
            ],
        );

        $this->importId     = $import->id;
        $this->importTag    = $tag;
        $this->importStatus = \App\Models\ContactImport::STATUS_PENDING;
        $this->step         = 'importing';

        unset($this->emailSenders);
    }

    public function checkImportProgress(): void
    {
        if ($this->step !== 'importing' || $this->importId === null) {
            return;
        }

        $import = \App\Models\ContactImport::find($this->importId);
        if ($import === null) {
            return;
        }

        $this->totalRows     = (int) $import->total_rows;
        $this->importedCount = (int) $import->imported_rows;
        $this->skippedCount  = (int) $import->skipped_rows;
        $this->invalidCount  = (int) $import->invalid_rows;
        $this->importStatus  = (string) $import->status;
        $this->importError   = $import->last_error;

        if ($this->importStatus === \App\Models\ContactImport::STATUS_COMPLETED) {
            $this->campaignName = 'Email blast — ' . ($import->original_name ?? 'list');
            $this->step         = 'compose';
        }
    }

    public function retryImport(): void
    {
        if ($this->importId === null || $this->importStatus !== \App\Models\ContactImport::STATUS_FAILED) {
            return;
        }
        $import = \App\Models\ContactImport::find($this->importId);
        if (! $import) return;

        $import->update(['status' => \App\Models\ContactImport::STATUS_PENDING, 'last_error' => null]);
        $this->importStatus = \App\Models\ContactImport::STATUS_PENDING;
        $this->importError  = null;

        \App\Jobs\ImportEmailRecipients::dispatch(
            $import->id,
            $this->storedPath,
            $this->extension,
            [
                'email'  => $this->emailColumn,
                'name'   => $this->nameColumn ?: null,
                'custom' => array_values(array_filter($this->customColumns)),
            ],
        );
    }

    #[Computed]
    public function importProgressPercent(): int
    {
        if ($this->totalRows <= 0) return 0;
        $done = $this->importedCount + $this->skippedCount + $this->invalidCount;
        return min(100, (int) floor(($done / $this->totalRows) * 100));
    }

    public function gotoReview(): void
    {
        $this->validate([
            'campaignName' => 'required|string|max:120',
            'subject'      => 'required|string|max:200',
            'body'         => 'required|string|max:20000',
            'senderPageId' => 'required|integer|exists:pages,id',
            'dailyCap'     => 'required|integer|min:1|max:10000',
            'jitterMin'    => 'required|integer|min:0|max:3600',
            'jitterMax'    => 'required|integer|min:0|max:3600',
        ]);

        if ($this->jitterMax < $this->jitterMin) {
            $this->addError('jitterMax', 'Jitter max must be ≥ min.');
            return;
        }

        $this->step = 'review';
    }

    #[Computed]
    public function reviewStats(): array
    {
        $team = Auth::user()->currentTeam;
        $total = $this->importTag
            ? Contact::where('team_id', $team->id)
                ->whereJsonContains('tags', $this->importTag)
                ->whereNotNull('email')
                ->count()
            : 0;

        $days = $this->dailyCap > 0 ? (int) ceil($total / max(1, $this->dailyCap)) : 0;

        return [
            'total' => $total,
            'days'  => $days,
        ];
    }

    public function launch(CampaignDispatcher $dispatcher): void
    {
        $team = Auth::user()->currentTeam;

        // Phase 2 monthly-cap gate — see WhatsAppWizard@launch comment.
        abort_unless(
            $team->canCreateCampaign(),
            429,
            __('You have reached your monthly campaign limit (:used/:limit). Upgrade your plan to launch another.', [
                'used'  => $team->campaignsCreatedThisMonth(),
                'limit' => $team->monthlyCampaignLimit(),
            ])
        );

        $campaign = Campaign::create([
            'team_id'            => $team->id,
            'created_by'         => Auth::id(),
            'platform'           => 'email',
            'name'               => $this->campaignName,
            'type'               => 'broadcast',
            'subject'            => $this->subject,
            'message_template'   => $this->body,
            'sender_page_id'     => $this->senderPageId,
            'daily_cap'          => $this->dailyCap,
            'jitter_min_seconds' => $this->jitterMin,
            'jitter_max_seconds' => $this->jitterMax,
            'ai_personalize'     => $this->aiPersonalize,
            'target_criteria'    => [
                'contact_tag' => $this->importTag,
            ],
            'status'             => Campaign::STATUS_ACTIVE,
        ]);

        $dispatcher->schedule($campaign);

        $this->createdCampaignId = $campaign->id;
        $this->step = 'launched';
    }

    public function render()
    {
        return view('livewire.campaigns.email-wizard')
            ->layout('layouts.app', ['title' => 'New Email Campaign']);
    }
}
