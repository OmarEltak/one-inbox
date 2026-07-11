<?php

namespace App\Livewire\Settings;

use App\Models\AiConfig as AiConfigModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AiConfig extends Component
{
    public ?int $selectedPageId = null;

    // Business Info
    public string $business_description = '';
    public string $additional_instructions = '';
    public array $product_catalog = [];
    public array $pricing_info = [];
    public array $faq = [];

    // Tone & Language
    public string $tone = 'friendly';
    public string $language = 'en';

    // Timing — defaults chosen to batch bursts of rapid customer messages
    // into a single AI reply, saving tokens.
    public int $response_delay_min_seconds = 60;
    public int $response_delay_max_seconds = 120;

    // Working Hours
    public array $working_hours = [];
    public bool $is_24_7 = false;
    public string $timezone = 'Africa/Cairo';

    // Sales Goal + Handoff
    public string $sales_goal_preset       = AiConfigModel::GOAL_INFO_ONLY;
    public array  $required_capture_fields = [];
    public array  $escalation_keywords     = [];
    public bool   $escalate_on_media       = false;
    public array  $escalation_topics       = [];
    public int    $contact_ai_reply_cap    = 20;

    // Toggle
    public bool $is_active = true;

    // UI state
    public bool   $hasConfig = false;
    public string $activeTab = 'sales_goal'; // sales_goal | knowledge | behavior | handoff | advanced

    public function mount(): void
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            $this->redirectRoute('dashboard');

            return;
        }

        // Auto-select first page if available
        $firstPage = $team->pages()->where('is_active', true)->first();
        if ($firstPage) {
            $this->selectPage($firstPage->id);
        }
    }

    public function selectPage(int $pageId): void
    {
        $team = Auth::user()->currentTeam;
        $page = $team->pages()->where('id', $pageId)->where('is_active', true)->first();

        if (! $page) {
            return;
        }

        $this->selectedPageId = $pageId;
        $config = $page->aiConfig;

        if ($config) {
            $this->hasConfig = true;
            $this->business_description = $config->business_description ?? '';
            $this->additional_instructions = $config->system_prompt ?? '';
            $this->product_catalog = $config->product_catalog ?? [];
            $this->pricing_info = $config->pricing_info ?? [];
            $this->faq = $config->faq ?? [];
            $this->tone = $config->tone ?? 'friendly';
            $this->language = $config->language ?? 'en';
            $this->response_delay_min_seconds = $config->response_delay_min_seconds ?? 30;
            $this->response_delay_max_seconds = $config->response_delay_max_seconds ?? 180;
            $this->working_hours = $config->working_hours ?? $this->defaultWorkingHours();
            $this->is_24_7 = (bool) ($config->is_24_7 ?? false);
            $this->timezone = $config->timezone ?? 'UTC';
            $this->sales_goal_preset       = $config->sales_goal_preset ?: AiConfigModel::GOAL_INFO_ONLY;
            $this->required_capture_fields = $config->required_capture_fields ?? [];
            $this->escalation_keywords     = $config->escalation_keywords ?? AiConfigModel::defaultEscalationKeywordsFor($this->sales_goal_preset);
            $this->escalate_on_media       = (bool) ($config->escalate_on_media ?? false);
            $this->escalation_topics       = $config->escalation_topics ?? [];
            $this->contact_ai_reply_cap    = (int) ($config->contact_ai_reply_cap ?? 20);
            $this->is_active = $config->is_active ?? true;
        } else {
            $this->hasConfig = false;
            $this->resetForm();
        }
    }

    public function saveConfig(): void
    {
        $team = Auth::user()->currentTeam;

        if (! $team || ! $this->selectedPageId) {
            return;
        }

        $page = $team->pages()->where('id', $this->selectedPageId)->first();
        if (! $page) {
            return;
        }

        $this->validate([
            'business_description' => 'required|string|min:10|max:1500',
            'tone' => 'required|in:professional,friendly,casual,formal',
            'language' => 'required|string|max:5',
            'response_delay_min_seconds' => 'required|integer|min:10|max:300',
            'response_delay_max_seconds' => 'required|integer|min:10|max:600',
            'timezone' => 'required|string',
            'sales_goal_preset' => 'required|in:info_only,capture_data,booking,ecommerce,custom',
            'contact_ai_reply_cap' => 'required|integer|min:' . AiConfigModel::CONTACT_CAP_MIN . '|max:' . AiConfigModel::CONTACT_CAP_MAX,
        ]);

        // Defence-in-depth: clamp the cap server-side even if a client sends
        // a value outside the input's declared range.
        $this->contact_ai_reply_cap = max(
            AiConfigModel::CONTACT_CAP_MIN,
            min(AiConfigModel::CONTACT_CAP_MAX, $this->contact_ai_reply_cap),
        );

        // Info-only preset never captures data, regardless of what's in the array.
        $captureFields = $this->sales_goal_preset === AiConfigModel::GOAL_INFO_ONLY
            ? []
            : array_values(array_filter($this->required_capture_fields, fn ($f) => ! empty(trim($f['key'] ?? ''))));

        $escalationKeywords = array_values(array_filter(
            array_map(fn ($k) => trim((string) $k), $this->escalation_keywords),
            fn ($k) => $k !== '',
        ));

        // Topic groups: drop empty labels and empty keyword rows so we don't
        // persist ghost entries the operator created but never filled in.
        $escalationTopics = array_values(array_filter(
            array_map(function ($topic) {
                $label = trim((string) ($topic['label'] ?? ''));
                $keywords = array_values(array_filter(
                    array_map(fn ($k) => trim((string) $k), $topic['keywords'] ?? []),
                    fn ($k) => $k !== '',
                ));

                return ['label' => $label, 'keywords' => $keywords];
            }, $this->escalation_topics),
            fn ($t) => $t['label'] !== '' && ! empty($t['keywords']),
        ));

        if ($this->response_delay_min_seconds > $this->response_delay_max_seconds) {
            $this->response_delay_max_seconds = $this->response_delay_min_seconds;
        }

        $data = [
            'page_id' => $this->selectedPageId,
            'team_id' => $team->id,
            'business_description' => $this->business_description,
            'system_prompt' => $this->additional_instructions ?: null,
            'product_catalog' => array_values(array_filter($this->product_catalog, fn ($item) => ! empty(trim($item['name'] ?? '')))),
            'pricing_info' => array_values(array_filter($this->pricing_info, fn ($item) => ! empty(trim($item['item'] ?? '')))),
            'faq' => array_values(array_filter($this->faq, fn ($item) => ! empty(trim($item['question'] ?? '')))),
            'tone' => $this->tone,
            'language' => $this->language,
            'response_delay_min_seconds' => $this->response_delay_min_seconds,
            'response_delay_max_seconds' => $this->response_delay_max_seconds,
            'working_hours' => $this->working_hours,
            'is_24_7' => $this->is_24_7,
            'timezone' => $this->timezone,
            'sales_goal_preset'        => $this->sales_goal_preset,
            'required_capture_fields'  => $captureFields,
            'escalation_keywords'      => $escalationKeywords,
            'escalate_on_media'        => $this->escalate_on_media,
            'escalation_topics'        => $escalationTopics,
            'contact_ai_reply_cap'     => $this->contact_ai_reply_cap,
            'is_active' => $this->is_active,
        ];

        AiConfigModel::updateOrCreate(
            ['page_id' => $this->selectedPageId],
            $data
        );

        $this->hasConfig = true;
        $this->dispatch('config-saved');
    }

    // --- Array field management ---

    public function addProduct(): void
    {
        $this->product_catalog[] = ['name' => '', 'description' => '', 'price' => ''];
    }

    public function removeProduct(int $index): void
    {
        unset($this->product_catalog[$index]);
        $this->product_catalog = array_values($this->product_catalog);
    }

    public function addPricing(): void
    {
        $this->pricing_info[] = ['item' => '', 'price' => '', 'notes' => ''];
    }

    public function removePricing(int $index): void
    {
        unset($this->pricing_info[$index]);
        $this->pricing_info = array_values($this->pricing_info);
    }

    public function addFaq(): void
    {
        $this->faq[] = ['question' => '', 'answer' => ''];
    }

    public function removeFaq(int $index): void
    {
        unset($this->faq[$index]);
        $this->faq = array_values($this->faq);
    }

    // --- Sales Goal preset switching + capture-field mgmt ---

    /**
     * Apply a sales-goal preset: pre-populate required fields + escalation
     * keywords with the preset's defaults. Non-destructive for the operator's
     * existing settings — only fills fields they haven't customized yet.
     */
    public function applySalesGoalPreset(string $preset): void
    {
        $allowed = [
            AiConfigModel::GOAL_INFO_ONLY,
            AiConfigModel::GOAL_CAPTURE_DATA,
            AiConfigModel::GOAL_BOOKING,
            AiConfigModel::GOAL_ECOMMERCE,
            AiConfigModel::GOAL_CUSTOM,
        ];
        if (! in_array($preset, $allowed, true)) {
            return;
        }

        $this->sales_goal_preset = $preset;

        // Custom preset: leave whatever the operator has, they're driving.
        if ($preset === AiConfigModel::GOAL_CUSTOM) {
            return;
        }

        $this->required_capture_fields = AiConfigModel::defaultCaptureFieldsFor($preset);
        $this->escalation_keywords     = AiConfigModel::defaultEscalationKeywordsFor($preset);
    }

    public function addCaptureField(): void
    {
        $this->required_capture_fields[] = ['key' => '', 'label' => '', 'type' => 'text'];
    }

    public function removeCaptureField(int $index): void
    {
        unset($this->required_capture_fields[$index]);
        $this->required_capture_fields = array_values($this->required_capture_fields);
    }

    public function addEscalationKeyword(): void
    {
        $this->escalation_keywords[] = '';
    }

    public function removeEscalationKeyword(int $index): void
    {
        unset($this->escalation_keywords[$index]);
        $this->escalation_keywords = array_values($this->escalation_keywords);
    }

    public function addEscalationTopic(): void
    {
        $this->escalation_topics[] = ['label' => '', 'keywords' => ['']];
    }

    public function removeEscalationTopic(int $index): void
    {
        unset($this->escalation_topics[$index]);
        $this->escalation_topics = array_values($this->escalation_topics);
    }

    public function addTopicKeyword(int $topicIndex): void
    {
        if (! isset($this->escalation_topics[$topicIndex])) {
            return;
        }
        $this->escalation_topics[$topicIndex]['keywords'][] = '';
    }

    public function removeTopicKeyword(int $topicIndex, int $keywordIndex): void
    {
        if (! isset($this->escalation_topics[$topicIndex]['keywords'][$keywordIndex])) {
            return;
        }
        unset($this->escalation_topics[$topicIndex]['keywords'][$keywordIndex]);
        $this->escalation_topics[$topicIndex]['keywords'] = array_values($this->escalation_topics[$topicIndex]['keywords']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // --- Helpers ---

    protected function resetForm(): void
    {
        $this->business_description = '';
        $this->additional_instructions = '';
        $this->product_catalog = [];
        $this->pricing_info = [];
        $this->faq = [];
        $this->tone = 'friendly';
        $this->language = 'en';
        $this->response_delay_min_seconds = 60;
        $this->response_delay_max_seconds = 120;
        $this->working_hours = $this->defaultWorkingHours();
        $this->is_24_7 = false;
        $this->timezone = 'UTC';
        $this->sales_goal_preset       = AiConfigModel::GOAL_INFO_ONLY;
        $this->required_capture_fields = [];
        $this->escalation_keywords     = AiConfigModel::defaultEscalationKeywordsFor(AiConfigModel::GOAL_INFO_ONLY);
        $this->escalate_on_media       = false;
        $this->escalation_topics       = [];
        $this->contact_ai_reply_cap    = 20;
        $this->is_active = true;
    }

    protected function defaultWorkingHours(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return collect($days)->mapWithKeys(fn ($day) => [
            $day => [
                'enabled' => ! in_array($day, ['saturday', 'sunday']),
                'start' => '09:00',
                'end' => '17:00',
            ],
        ])->all();
    }

    public function getPages()
    {
        $team = Auth::user()->currentTeam;

        return $team ? $team->pages()->where('is_active', true)->get() : collect();
    }

    public function render()
    {
        return view('livewire.settings.ai-config', [
            'pages' => $this->getPages(),
        ])->layout('layouts.app', ['title' => 'AI Configuration']);
    }
}
