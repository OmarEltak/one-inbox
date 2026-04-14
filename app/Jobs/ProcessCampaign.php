<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Conversation;
use App\Models\Message;
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
        $pageId   = $criteria['page_id'] ?? null;

        if (! $pageId) {
            $campaign->update(['status' => 'failed']);
            return;
        }

        $page = Page::where('is_active', true)
            ->where('team_id', $campaign->team_id)
            ->find($pageId);

        if (! $page) {
            $campaign->update(['status' => 'failed']);
            return;
        }

        $platform = $campaign->platform ?? 'whatsapp';

        if ($platform === 'whatsapp') {
            $this->processWhatsApp($campaign, $page, $criteria, $whatsapp);
        } else {
            $this->processDirectMessage($campaign, $page, $criteria, $platform);
        }
    }

    protected function processDirectMessage(Campaign $campaign, Page $page, array $criteria, string $platform): void
    {
        // Cursor prevents re-sending to already-messaged contacts on resume
        $lastId   = (int) ($criteria['last_conversation_id'] ?? 0);
        $isResume = $lastId > 0;

        // Recalculate total for accurate display
        $totalCount = $this->buildConversationQuery($page->id, $platform, $criteria)
            ->count();

        if (! $isResume) {
            $campaign->update(['total_contacts' => $totalCount, 'sent_count' => 0]);
        }

        $conversations = $this->buildConversationQuery($page->id, $platform, $criteria)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->get();

        $remaining = $conversations->count();
        $sent      = $isResume ? $campaign->sent_count : 0;

        $delaySeconds = (int) ($criteria['delay_seconds'] ?? 3);
        $delaySeconds = max(1, min(30, $delaySeconds));

        foreach ($conversations->values() as $index => $conversation) {
            $campaign->refresh();
            if ($campaign->status !== 'active') {
                break;
            }

            try {
                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'direction'       => 'outbound',
                    'sender_type'     => 'campaign',
                    'content_type'    => 'text',
                    'content'         => $campaign->message_template,
                ]);

                // Dispatch is optimistic — SendPlatformMessage handles delivery + retries
                SendPlatformMessage::dispatch($message->id);

                $sent++;
                $this->saveProgress($campaign, $sent, $conversation->id);
            } catch (\Throwable $e) {
                Log::warning('Campaign send failed for conversation', [
                    'campaign_id'     => $campaign->id,
                    'conversation_id' => $conversation->id,
                    'error'           => $e->getMessage(),
                ]);
            }

            if ($index < $remaining - 1) {
                sleep($delaySeconds);
            }
        }

        $this->finalize($campaign, $sent);
    }

    protected function processWhatsApp(Campaign $campaign, Page $page, array $criteria, WhatsAppPlatform $whatsapp): void
    {
        $lastId   = (int) ($criteria['last_conversation_id'] ?? 0);
        $isResume = $lastId > 0;

        $totalCount = $this->buildConversationQuery($page->id, 'whatsapp', $criteria)
            ->count();

        if (! $isResume) {
            $campaign->update(['total_contacts' => $totalCount, 'sent_count' => 0]);
        }

        $conversations = $this->buildConversationQuery($page->id, 'whatsapp', $criteria)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->get();

        $remaining = $conversations->count();
        $sent      = $isResume ? $campaign->sent_count : 0;

        $delaySeconds = (int) ($criteria['delay_seconds'] ?? 10);
        $delaySeconds = max(3, min(60, $delaySeconds));

        foreach ($conversations->values() as $index => $conversation) {
            $campaign->refresh();
            if ($campaign->status !== 'active') {
                break;
            }

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
                    $this->saveProgress($campaign, $sent, $conversation->id);
                }
            } catch (\Throwable $e) {
                Log::warning('Campaign send failed for conversation', [
                    'campaign_id'     => $campaign->id,
                    'conversation_id' => $conversation->id,
                    'error'           => $e->getMessage(),
                ]);
            }

            if ($index < $remaining - 1) {
                sleep($delaySeconds);
            }
        }

        $this->finalize($campaign, $sent);
    }

    protected function buildConversationQuery(int $pageId, string $platform, array $criteria)
    {
        $query = Conversation::where('page_id', $pageId)->where('platform', $platform);

        if (! empty($criteria['lead_status'])) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_status', $criteria['lead_status']));
        }

        return $query;
    }

    protected function saveProgress(Campaign $campaign, int $sent, int $lastConversationId): void
    {
        $criteria = $campaign->target_criteria ?? [];
        $criteria['last_conversation_id'] = $lastConversationId;

        $campaign->update(['sent_count' => $sent, 'target_criteria' => $criteria]);
        $campaign->setRelations([]); // clear cached relations so refresh() re-queries
    }

    protected function finalize(Campaign $campaign, int $sent): void
    {
        $campaign->refresh();

        // Only mark completed if still active — preserve paused/cancelled status
        if ($campaign->status === 'active') {
            // Clear cursor so the next launch starts fresh
            $criteria = $campaign->target_criteria ?? [];
            unset($criteria['last_conversation_id']);
            $campaign->update(['status' => 'completed', 'sent_count' => $sent, 'target_criteria' => $criteria]);
        }
    }
}
