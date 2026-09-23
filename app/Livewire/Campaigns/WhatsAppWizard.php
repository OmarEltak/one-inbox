<?php

declare(strict_types=1);

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Page;
use App\Services\Campaigns\CampaignScheduler;
use App\Services\Campaigns\PhoneContactImporter;
use App\Services\Campaigns\SupportedCountries;
use App\Services\Email\SpreadsheetParser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WhatsAppWizard extends Component
{
    use WithFileUploads;

    /** Ordered step machine — kept as a const so the Blade indicator and back() share one source of truth. */
    public const STEPS = ['upload', 'map', 'compose', 'test', 'review', 'launched'];

    public string $step = 'upload';
    public $file = null;

    public ?string $storedPath = null;
    public ?string $originalName = null;
    public ?string $extension = null;

    public array $detectedHeaders = [];
    public array $previewRows = [];

    public string $phoneColumn = '';
    public string $nameColumn = '';
    public array  $customColumns = [];
    public string $defaultCountry = 'EG';

    public string $campaignName = '';
    public string $body = "Hi {{name}},\n\n";
    public ?int $senderPageId = null;
    public int $jitterMin = 30;
    public int $jitterMax = 60;

    public ?int $importId = null;
    public ?string $importTag = null;
    public int $importedCount = 0;
    public int $skippedCount  = 0;
    public int $invalidCount  = 0;
    public ?int $createdCampaignId = null;

    public string $testPhone = '';
    public string $testName  = 'Test';
    public ?bool  $testResult = null;
    public ?string $testError = null;

    /** Clear the pending upload so the operator can swap files without leaving the step. */
    public function removeFile(): void
    {
        $this->reset(['file', 'storedPath', 'originalName', 'extension', 'detectedHeaders', 'previewRows']);
        $this->resetValidation('file');
    }

    /** Go back one step. Safe: never advances, never mutates DB rows. */
    public function back(): void
    {
        $idx = array_search($this->step, self::STEPS, true);
        if ($idx === false || $idx === 0) {
            return;
        }
        // From 'launched' we don't offer back — the campaign is real.
        if ($this->step === 'launched') {
            return;
        }
        $this->step = self::STEPS[$idx - 1];
        // Coming back from test wipes the last test result — old feedback would be misleading.
        if ($this->step === 'compose') {
            $this->testResult = null;
            $this->testError = null;
        }
    }

    public function mount(): void
    {
        $team = Auth::user()?->currentTeam;
        if (! $team || ! $team->hasFeature('bulk_whatsapp_campaigns')) {
            throw new AccessDeniedHttpException('Bulk WhatsApp campaigns is not enabled for this team.');
        }

        $first = Page::where('team_id', $team->id)
            ->where('platform', 'whatsapp')
            ->where('is_active', true)
            ->first();
        $this->senderPageId = $first?->id;

        $configured = strtoupper((string) config('campaigns.default_country', 'EG'));
        $this->defaultCountry = SupportedCountries::has($configured) ? $configured : 'EG';
    }

    #[Computed]
    public function whatsappSenders()
    {
        return Page::where('team_id', Auth::user()->currentTeam->id)
            ->where('platform', 'whatsapp')
            ->where('is_active', true)
            ->get();
    }

    #[Computed]
    public function countries(): array
    {
        return SupportedCountries::LIST;
    }

    #[Computed]
    public function stepIndex(): int
    {
        $i = array_search($this->step, self::STEPS, true);
        return $i === false ? 0 : (int) $i;
    }

    public function advanceToMap(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        $teamId = $this->currentTeamId();
        $this->storedPath = $this->file->store("imports/{$teamId}");
        $this->originalName = $this->file->getClientOriginalName();
        $this->extension = strtolower($this->file->getClientOriginalExtension() ?: 'csv');

        $absolute = Storage::path($this->storedPath);
        $parser = new SpreadsheetParser($absolute, $this->extension);
        $preview = $parser->preview(20);
        $this->detectedHeaders = $preview['headers'];
        $this->previewRows = $preview['rows'];

        $this->step = 'map';
    }

    public function advanceToCompose(): void
    {
        $supported = array_column(SupportedCountries::LIST, 'iso2');
        $this->validate([
            'phoneColumn'    => 'required|string',
            'defaultCountry' => ['required', 'string', 'size:2', Rule::in($supported)],
        ]);

        $absolute = Storage::path($this->storedPath);
        $parser = new SpreadsheetParser($absolute, $this->extension);
        $rows = iterator_to_array($parser->stream());

        $importer = app(PhoneContactImporter::class);
        $result = $importer->import(
            teamId: $this->currentTeamId(),
            channel: 'whatsapp',
            filename: $this->originalName ?? basename($this->storedPath),
            defaultCountry: strtoupper($this->defaultCountry),
            phoneColumn: $this->phoneColumn,
            nameColumn: $this->nameColumn ?: null,
            optedInAtColumn: null,
            customColumns: $this->customColumns,
            rows: $rows,
        );

        $this->importId = $result->importId;
        $this->importedCount = $result->importedRows;
        $this->skippedCount  = $result->skippedRows;
        $this->invalidCount  = $result->invalidRows;
        $this->importTag = 'imported:' . pathinfo($this->originalName ?? $this->storedPath, PATHINFO_FILENAME);
        $this->step = 'compose';
    }

    public function advanceToTest(): void
    {
        $this->validate([
            'campaignName' => 'required|string|max:100',
            'body'         => 'required|string|max:2000',
            'senderPageId' => 'required|integer',
            'jitterMin'    => 'required|integer|min:15|max:600',
            'jitterMax'    => 'required|integer|min:15|max:600|gte:jitterMin',
        ]);
        $this->step = 'test';
    }

    public function sendTest(\App\Services\Wuzapi\WhatsAppSender $sender): void
    {
        $this->validate([
            'testPhone' => 'required|string',
            'testName'  => 'nullable|string|max:80',
        ]);

        $page = Page::findOrFail($this->senderPageId);
        $body = str_replace(
            ['{{name}}', '{{phone}}'],
            [$this->testName, $this->testPhone],
            $this->body,
        );
        $result = $sender->send($page, $this->testPhone, $body);
        $this->testResult = $result->sent;
        $this->testError = $result->error;
    }

    public function advanceToReview(): void
    {
        abort_unless($this->testResult === true, 422, 'You must successfully test-send before launching.');
        $this->step = 'review';
    }

    public function launch(CampaignScheduler $scheduler): void
    {
        $team = Auth::user()->currentTeam;

        $campaign = Campaign::create([
            'team_id'            => $team->id,
            'created_by'         => Auth::id(),
            'name'               => $this->campaignName,
            'type'               => 'promotion',
            'platform'           => 'whatsapp',
            'sender_page_id'     => $this->senderPageId,
            'message_template'   => $this->body,
            'target_criteria'    => ['contact_import_id' => $this->importId],
            'jitter_min_seconds' => $this->jitterMin,
            'jitter_max_seconds' => $this->jitterMax,
            'status'             => 'active',
        ]);

        $phones = Contact::where('team_id', $team->id)
            ->whereJsonContains('tags', $this->importTag)
            ->pluck('phone')
            ->filter()
            ->values()
            ->all();

        $scheduler->schedule($campaign, $phones, channel: 'whatsapp');

        $this->createdCampaignId = $campaign->id;
        $this->step = 'launched';
    }

    private function currentTeamId(): int
    {
        return (int) Auth::user()->currentTeam->id;
    }

    public function render()
    {
        return view('livewire.campaigns.whats-app-wizard')
            ->layout('layouts.app', ['title' => 'New WhatsApp Campaign']);
    }
}
