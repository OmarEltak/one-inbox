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

    // Timing
    public int $response_delay_min_seconds = 30;
    public int $response_delay_max_seconds = 180;

    // Working Hours
    public array $working_hours = [];
    public string $timezone = 'Africa/Cairo';

    // Toggle
    public bool $is_active = true;

    // UI state
    public bool $hasConfig = false;

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
            $this->timezone = $config->timezone ?? 'UTC';
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
        ]);

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
            'timezone' => $this->timezone,
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
        $this->response_delay_min_seconds = 30;
        $this->response_delay_max_seconds = 180;
        $this->working_hours = $this->defaultWorkingHours();
        $this->timezone = 'UTC';
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
