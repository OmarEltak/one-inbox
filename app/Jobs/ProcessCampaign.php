<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Conversation;
use App\Models\Page;
use App\Services\Platforms\WhatsAppPlatform;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessCampaign implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $campaignId) {}

    public function handle(WhatsAppPlatform $whatsapp): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign || $campaign->status !== 'active') {
            return;
        }

        $criteria = $campaign->target_criteria ?? [];
        $pageId = $criteria['page_id'] ?? null;

        if (! $pageId) {
            $campaign->update(['status' => 'completed']);
            return;
        }

        $page = Page::where('is_active', true)->find($pageId);

        if (! $page) {
            $campaign->update(['status' => 'completed']);
            return;
        }

        // Build conversation query — each WhatsApp conversation IS the contact's phone
        $query = Conversation::with('contact')
            ->where('page_id', $pageId)
            ->where('platform', 'whatsapp');

        // Optional lead_status filter via contact
        if (! empty($criteria['lead_status'])) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_status', $criteria['lead_status']));
        }

        $conversations = $query->get();
        $campaign->update(['total_contacts' => $conversations->count()]);

        $sent = 0;

        foreach ($conversations as $conversation) {
            $phone = $conversation->platform_conversation_id;

            if (! $phone) {
                continue;
            }

            try {
                $result = $whatsapp->sendTemplate(
                    $page,
                    $phone,
                    $campaign->message_template,
                    $criteria['language_code'] ?? 'en',
                );

                if ($result) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::warning('Campaign send failed for conversation', [
                    'campaign_id'     => $campaign->id,
                    'conversation_id' => $conversation->id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        $campaign->update([
            'sent_count' => $sent,
            'status'     => 'completed',
        ]);
    }
}
