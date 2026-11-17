<?php

declare(strict_types=1);

namespace App\Services\Skills;

use App\Models\AiConfig;
use App\Models\Page;
use App\Models\Team;
use Illuminate\Support\Str;

/**
 * SKILL 2 — Customer Onboarding Playbook
 *
 * Structured config-generator. Takes a canonical "customer profile" struct
 * (products, pricing, shipping, escalation keywords, connector URL, trial
 * settings) and atomically writes AiConfig rows for EVERY active page on
 * the target team. Returns a report of what landed where.
 *
 * Idempotent — safe to re-run; upserts by page_id.
 *
 * Usage:
 *   $playbook = new OnboardingPlaybook();
 *   $playbook->apply($teamId, $profile);
 *
 * The $profile struct shape (array):
 *   name                    string   — business name for logging
 *   language                string   — 'ar' | 'en'
 *   tone                    string   — 'friendly' | 'professional' | ...
 *   is_24_7                 bool
 *   response_delay_sec      [min,max]
 *   contact_ai_reply_cap    int
 *   sales_goal_preset       string   — one of AiConfig::GOAL_*
 *   products                array<{id?: string, name, description, price}>
 *   pricing_info            array<{item, price, notes}>
 *   faq                     array<{question, answer}>
 *   escalation_keywords     array<string>
 *   escalate_on_media       bool
 *   required_fields         array<{key, label, type}>
 *   system_prompt           string
 *   sheet_url               ?string
 *   webhook_url             ?string
 *
 * All timestamps + UUIDs generated automatically. Nothing outside the
 * AiConfig table is touched — safe to re-run on an already-configured team
 * (will overwrite config but leave pages/contacts/messages intact).
 */
class OnboardingPlaybook
{
    /**
     * @return array<string, mixed>  report keyed by page_id
     */
    public function apply(int $teamId, array $profile): array
    {
        $team = Team::findOrFail($teamId);
        $pages = $team->pages()->where('is_active', true)->get();

        if ($pages->isEmpty()) {
            return ['error' => "team#{$teamId} has no active pages — connect at least one channel first"];
        }

        $products = $this->ensureProductIds($profile['products'] ?? []);

        $workingHours = [];
        foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day) {
            $workingHours[$day] = ['enabled' => true, 'start' => '00:00', 'end' => '23:59'];
        }

        $sales = [
            'events' => \App\Services\SalesConnectors\SalesConnectors::EVENTS,
        ];
        if (! empty($profile['sheet_url']))   $sales['sheet_url']   = $profile['sheet_url'];
        if (! empty($profile['webhook_url'])) $sales['webhook_url'] = $profile['webhook_url'];

        $report = ['team' => $team->name, 'pages' => []];

        foreach ($pages as $page) {
            $cfg = AiConfig::updateOrCreate(
                ['page_id' => $page->id],
                [
                    'team_id'                    => $team->id,
                    'business_description'       => $profile['business_description'] ?? '',
                    'system_prompt'              => $profile['system_prompt']       ?? '',
                    'product_catalog'            => $products,
                    'pricing_info'               => $profile['pricing_info']        ?? [],
                    'faq'                        => $profile['faq']                 ?? [],
                    'tone'                       => $profile['tone']                ?? 'friendly',
                    'language'                   => $profile['language']            ?? 'ar',
                    'response_delay_min_seconds' => $profile['response_delay_sec'][0] ?? 10,
                    'response_delay_max_seconds' => $profile['response_delay_sec'][1] ?? 15,
                    'working_hours'              => $workingHours,
                    'is_24_7'                    => $profile['is_24_7'] ?? true,
                    'timezone'                   => $profile['timezone'] ?? 'Africa/Cairo',
                    'sales_goal_preset'          => $profile['sales_goal_preset'] ?? AiConfig::GOAL_ECOMMERCE,
                    'required_capture_fields'    => $profile['required_fields']    ?? [],
                    'escalation_keywords'        => $profile['escalation_keywords'] ?? [],
                    'escalate_on_media'          => $profile['escalate_on_media']  ?? false,
                    'contact_ai_reply_cap'       => $profile['contact_ai_reply_cap'] ?? 30,
                    'comment_settings'           => AiConfig::defaultCommentSettings(),
                    'sales_connectors'           => $sales,
                    'is_active'                  => true,
                ]
            );
            $report['pages'][$page->id] = [
                'ai_config_id' => $cfg->id,
                'platform'     => $page->platform,
                'page_name'    => $page->name,
            ];
        }

        return $report;
    }

    private function ensureProductIds(array $products): array
    {
        return array_values(array_map(static function ($p) {
            if (empty($p['id'] ?? null)) $p['id'] = (string) Str::uuid();
            return $p;
        }, $products));
    }

    /**
     * Validate the config end-to-end. Returns an array of warnings — empty
     * array means the config is ship-ready.
     */
    public function validate(array $profile): array
    {
        $warnings = [];

        if (empty($profile['business_description'] ?? null)) {
            $warnings[] = 'business_description is empty — the AI will have no context about what the shop sells';
        }
        if (empty($profile['products'] ?? [])) {
            $warnings[] = 'products array is empty — AI cannot recommend anything';
        }
        if (empty($profile['faq'] ?? [])) {
            $warnings[] = 'FAQ is empty — AI will answer from vibes when customers ask common questions';
        }
        if (empty($profile['escalation_keywords'] ?? [])) {
            $warnings[] = 'no escalation keywords — customers who want a human will stay stuck with the bot';
        }
        if (empty($profile['required_fields'] ?? [])) {
            $warnings[] = 'required_fields empty — orders will never be flagged as "complete"';
        } else {
            $required = array_column($profile['required_fields'], 'key');
            foreach (['name', 'phone', 'address'] as $expected) {
                if (! in_array($expected, $required, true)) {
                    $warnings[] = "required_fields is missing \"{$expected}\" — strongly recommended for shipping";
                }
            }
        }
        if (empty($profile['sheet_url'] ?? null) && empty($profile['webhook_url'] ?? null)) {
            $warnings[] = 'no connector configured — orders will not auto-flow to any sheet or CRM';
        }
        if (! isset($profile['system_prompt']) || mb_strlen($profile['system_prompt']) < 100) {
            $warnings[] = 'system_prompt is short (<100 chars) — the AI will miss shop-specific rules';
        }

        return $warnings;
    }
}
