<?php
/**
 * Send a correctly signed fake Meta webhook (Messenger or Instagram DM) to a LOCAL app,
 * exercising the real pipeline: signature check → webhook_logs → ProcessIncomingMessage
 * → page routing → conversation/message rows. Never point this at production.
 *
 * Run via tinker so config() + Http are available:
 *   WH_PLATFORM=instagram WH_ENTRY=17841400000000001 WH_SENDER=555 WH_TEXT="hi" \
 *     php artisan tinker --execute="require '.claude/skills/platform-messaging-testing/scripts/send-test-webhook.php';"
 *   php artisan queue:work --once --queue=urgent      # process it (unless QUEUE_CONNECTION=sync)
 *
 * Env: WH_PLATFORM (facebook|instagram, default instagram), WH_ENTRY (page/IG id = entry.id),
 *      WH_SENDER (customer id), WH_TEXT, WH_URL (default APP_URL), WH_PATH (/api/webhooks/meta),
 *      WH_SECRET_KEY (config key used to sign: app_secret | instagram_app_secret, default app_secret)
 */

use Illuminate\Support\Facades\Http;

$platform = getenv('WH_PLATFORM') ?: 'instagram';
$entryId  = getenv('WH_ENTRY') ?: throw new RuntimeException('Set WH_ENTRY to the page platform_page_id');
$sender   = getenv('WH_SENDER') ?: '5550001';
$text     = getenv('WH_TEXT') ?: 'test message '.now()->toTimeString();
$base     = rtrim(getenv('WH_URL') ?: config('app.url'), '/');
$path     = getenv('WH_PATH') ?: '/api/webhooks/meta';
$secret   = config('services.meta.'.(getenv('WH_SECRET_KEY') ?: 'app_secret'));

if (! str_contains($base, '127.0.0.1') && ! str_contains($base, 'localhost') && ! str_ends_with(parse_url($base, PHP_URL_HOST) ?? '', '.test')) {
    throw new RuntimeException("Refusing to send a fake webhook to non-local {$base}");
}

$payload = [
    'object' => $platform === 'instagram' ? 'instagram' : 'page',
    'entry'  => [[
        'id'        => (string) $entryId,
        'time'      => now()->getTimestampMs(),
        'messaging' => [[
            'sender'    => ['id' => (string) $sender],
            'recipient' => ['id' => (string) $entryId],
            'timestamp' => now()->getTimestampMs(),
            'message'   => ['mid' => 'test-mid-'.uniqid(), 'text' => $text],
        ]],
    ]],
];
$body = json_encode($payload);

$resp = Http::withHeaders([
    'Content-Type'        => 'application/json',
    'X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $body, (string) $secret),
])->withBody($body, 'application/json')->post($base.$path);

echo "POST {$base}{$path} → HTTP {$resp->status()} ".\Illuminate\Support\Str::limit(strip_tags($resp->body()), 40)."\n";
echo "Latest webhook_logs id: ".\App\Models\WebhookLog::max('id')."\n";
