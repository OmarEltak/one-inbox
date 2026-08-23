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
        $raw = $request->all();

        // Current Wuzapi ships events as:
        //   { instanceName, userID, jsonData: "{...}" }  <- jsonData is a STRING
        // where the parsed jsonData is:
        //   { type: "Message"|"ReadReceipt"|..., event: { Info: {...}, Message: {...} } }
        //
        // ProcessIncomingMessage::processWuzapi() was written against an older shape
        // that expected `event` and `instance` at top level plus `data.Info` /
        // `data.Message` underneath. Normalize here so the processor stays simple
        // and old rows in webhook_logs (with the raw shape) can still be replayed
        // by re-dispatching through this normaliser.
        $inner = [];
        if (isset($raw['jsonData']) && is_string($raw['jsonData'])) {
            $inner = json_decode($raw['jsonData'], true) ?? [];
        }

        $payload = [
            'event'    => $inner['type'] ?? ($raw['event'] ?? 'unknown'),
            'instance' => $raw['instanceName'] ?? ($raw['instance'] ?? null),
            'userID'   => $raw['userID'] ?? null,
            'data'     => [
                'Info'    => $inner['event']['Info'] ?? [],
                'Message' => $inner['event']['Message'] ?? [],
            ],
            'raw'      => $raw,
        ];

        $log = WebhookLog::create([
            'platform'   => 'whatsapp_gateway',  // same bucket as the legacy Evolution feed
            'event_type' => "wuzapi.{$payload['event']}",
            'payload'    => $payload,
        ]);

        ProcessIncomingMessage::dispatch($log->id);

        return response('OK', 200);
    }
}
