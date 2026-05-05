<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Wuzapi (whatsmeow-based WhatsApp gateway) webhook receiver.
 *
 * Wuzapi pushes events as POST JSON. The shape is:
 *   {
 *     "event":   "Message" | "ReadReceipt" | "Connected" | "Disconnected" | "PairSuccess" | ...
 *     "token":   "<per-user token, identifies which tenant this is for>",
 *     "instance":"<tenant name we set on user create>",
 *     "jid":     "201026361218@s.whatsapp.net",
 *     "data":    { ...event-specific payload... }
 *   }
 *
 * We don't sign-verify here — Wuzapi runs on localhost only (not exposed to the
 * internet) and traffic to /api/webhooks/wuzapi from outside is dropped at the
 * Cloudflare layer because there's no path matching from public internet to
 * Wuzapi other than this Laravel route. The token in the body is also our
 * tenant identifier, so an unrelated POST without it goes nowhere downstream.
 *
 * If Wuzapi changes payload shape:
 *   → update ProcessIncomingMessage::processWuzapi() and handleWuzapiMessage()
 */
class WuzapiWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->all();
        $event   = $payload['event'] ?? 'unknown';

        $log = WebhookLog::create([
            'platform'   => 'whatsapp_gateway',  // same bucket as the legacy Evolution feed
            'event_type' => "wuzapi.{$event}",
            'payload'    => $payload,
        ]);

        ProcessIncomingMessage::dispatch($log->id);

        return response('OK', 200);
    }
}
