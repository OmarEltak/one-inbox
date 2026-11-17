<?php
/**
 * Clean rebuild: sheet should contain ONLY real confirmed orders —
 * conversations where the AI actually emitted "سجلت الأوردر" with a
 * structured confirmation, AND the customer's typed data (name, phone,
 * address, order) was correctly captured into the AI's echo.
 *
 * Steps:
 *   1. For every Lavin conversation, retroactively run the parser
 *      against the AI's confirmation message (if any), re-populating
 *      captured_data.{name, phone, address, order_summary, confirmed_at}.
 *   2. For convos that have a confirmation but are missing name/phone/
 *      address/order in the captured data (because the AI violated its
 *      format OR because the customer never typed it), log them as
 *      "incomplete" — do NOT push to sheet. Operator can see these in
 *      a separate report.
 *   3. Push to sheet ONLY convos with confirmed_at AND full captured
 *      data. Each push respects the new "confirmed_at required" gate.
 */

use App\Jobs\PushSalesConnectorRow;
use App\Jobs\SendAiResponse;
use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\SalesConnectors\SalesConnectors;

const LAVIN_TEAM_ID = 60;

echo "=== STEP 1: find all AI-confirmed orders and re-parse ===\n\n";

$confirmed = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('sender_type', 'ai')
    ->where('direction', 'outbound')
    ->where('content', 'like', '%سجلت الأوردر%')
    ->orderBy('id')
    ->get()
    ->groupBy('conversation_id');

echo "Confirmed order conversations: " . $confirmed->count() . "\n\n";

// Reflection to invoke the protected parseAiOrderConfirmation on a fresh SendAiResponse
$job = new SendAiResponse(0, 0);
$reflection = new ReflectionClass($job);
$method = $reflection->getMethod('parseAiOrderConfirmation');
$method->setAccessible(true);

$complete = [];
$incomplete = [];

foreach ($confirmed as $convId => $msgs) {
    $conv = Conversation::find($convId);
    $latestConfirmation = $msgs->last()->content;

    // Reset captured_data to just the already-captured customer-typed values,
    // then re-apply the parser to re-stamp from the AI confirmation.
    $method->invoke($job, $conv, $latestConfirmation);
    $conv->refresh();
    $cap = $conv->captured_data ?? [];

    $hasAll = ! empty($cap['name'])
        && ! empty($cap['phone'])
        && ! empty($cap['address'])
        && ! empty($cap['order_summary']);

    if ($hasAll) {
        $complete[] = $conv;
    } else {
        $missing = [];
        foreach (['name', 'phone', 'address', 'order_summary'] as $k) {
            if (empty($cap[$k])) $missing[] = $k;
        }
        $incomplete[] = ['conv' => $conv, 'missing' => $missing];
    }
}

echo "COMPLETE orders (will push to sheet): " . count($complete) . "\n";
foreach ($complete as $c) {
    $cap = $c->captured_data;
    echo "  conv#{$c->id} platform={$c->platform} name=\"{$cap['name']}\" order=\"{$cap['order_summary']}\"\n";
}

echo "\nINCOMPLETE orders (AI emitted confirmation but data missing — NOT pushed):\n";
foreach ($incomplete as $x) {
    echo "  conv#{$x['conv']->id} missing: " . implode(', ', $x['missing']) . "\n";
}

echo "\n=== STEP 2: push the complete ones ===\n\n";

$pushed = 0;
foreach ($complete as $c) {
    try {
        PushSalesConnectorRow::dispatchSync($c->id, SalesConnectors::EVENT_LEAD_CAPTURED);
        $pushed++;
        echo "  pushed: conv#{$c->id}\n";
        usleep(400_000);
    } catch (\Throwable $e) {
        echo "  FAILED: conv#{$c->id}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== DONE ===\n";
echo "pushed to sheet: {$pushed}\n";
echo "incomplete orders (operator should chase on these): " . count($incomplete) . "\n";
