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

    public string $step = 'upload'; // upload | map | compose | review | launched

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
        $this->validate([
            'file' => 'required|file|max:10240|mimes:csv,txt,xlsx',
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

    public function confirmMapAndImport(ContactImporter $importer): void
    {
        $this->validate([
            'emailColumn' => 'required|string|in:'.implode(',', $this->detectedHeaders),
        ], [
            'emailColumn.required' => 'Pick which column contains the email address.',
            'emailColumn.in'       => 'Email column must match one of the detected headers.',
        ]);

        $team = Auth::user()->currentTeam;
        $absolute = Storage::disk('local')->path($this->storedPath);

        $parser = new SpreadsheetParser($absolute, $this->extension);
        $importerInstance = new ContactImporter($parser);

        $import = $importerInstance->import(
            teamId: $team->id,
            userId: Auth::id(),
            filename: $this->storedPath,
            originalName: $this->originalName,
            map: [
                'email'  => $this->emailColumn,
                'name'   => $this->nameColumn ?: null,
                'custom' => array_values(array_filter($this->customColumns)),
            ],
        );

        $this->importId = $import->id;
        $this->importTag = $import->tag;
        $this->importedCount = $import->imported_rows;
        $this->campaignName = 'Email blast — '.$import->original_name;
        $this->step = 'compose';

        unset($this->emailSenders);
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
