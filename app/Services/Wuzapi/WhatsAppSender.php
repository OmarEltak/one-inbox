<?php

declare(strict_types=1);

namespace App\Services\Wuzapi;

use App\Models\Page;
use App\Services\EvolutionApiService;
use Illuminate\Contracts\Encryption\DecryptException;
use Throwable;

/**
 * SOLE Wuzapi call site for campaign message sends.
 *
 * Do NOT introduce another WhatsApp send path in campaign code. Per project
 * memory (whatsapp_parallel_send_paths), user-triggered and AI-triggered
 * WhatsApp sends already duplicate; adding a third path via campaigns would
 * make the tangle worse.
 */
class WhatsAppSender
{
    public function __construct(private EvolutionApiService $wuzapi) {}

    public function send(Page $page, string $toE164, string $body): SendResult
    {
        try {
            // page_access_token is stored plain on legacy WhatsApp QR rows and
            // encrypted on newer ones. Same try/decrypt-then-plain fallback the
            // existing send paths use (see SendPlatformMessage::sendViaEvolution
            // and ProcessIncomingMessage::processWuzapi).
            try {
                $token = decrypt($page->page_access_token);
            } catch (DecryptException) {
                $token = (string) $page->page_access_token;
            }

            $instanceName = $page->metadata['gateway_instance'] ?? (string) $page->platform_page_id;

            $id = $this->wuzapi->sendText(
                $instanceName,
                (string) $token,
                ltrim($toE164, '+'),
                $body,
            );

            if ($id === null) {
                // EvolutionApiService swallows the HTTP response detail. Without
                // it we cannot distinguish transient (5xx, network) from permanent
                // (invalid recipient). Phase A: assume transient. Phase C wires
                // richer classification when we surface Wuzapi HTTP shape.
                return SendResult::transient('Wuzapi returned null from sendText');
            }

            return SendResult::ok($id);
        } catch (Throwable $e) {
            return SendResult::transient($e->getMessage());
        }
    }
}
