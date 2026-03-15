<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // Verify the secret token if configured
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (config('services.telegram.webhook_secret') && $secretToken !== config('services.telegram.webhook_secret')) {
            Log::warning('Telegram webhook secret mismatch');

            return response('Forbidden', 403);
        }

        $log = WebhookLog::create([
            'platform' => 'telegram',
            'event_type' => $this->detectEventType($request),
            'payload' => $request->all(),
        ]);

        ProcessIncomingMessage::dispatch($log->id);

        return response('OK', 200);
    }

    protected function detectEventType(Request $request): string
    {
        if ($request->has('message')) {
            return 'message';
        }
        if ($request->has('edited_message')) {
            return 'edited_message';
        }
        if ($request->has('callback_query')) {
            return 'callback_query';
        }

        return 'unknown';
    }
}
