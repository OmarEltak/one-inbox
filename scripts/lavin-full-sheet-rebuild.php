<?php
/**
 * Full Lavin sheet rebuild — user cleared the sheet manually so I can
 * re-push every conversation in the backfill window with the new column
 * structure (Date, Name, Phone, Order, Address, Channel, Page, Status,
 * Last message, Event, Conversation ID).
 *
 * The Apps Script deployed in Omar's sheet still has the old shape:
 *   if (sheet.getLastRow() === 0) sheet.appendRow(data.headers);
 * — which actually works fine for a cleared sheet: the first POST
 * after clearing will trigger that branch and write the new headers
 * (which come from our PHP's HEADERS constant, not from the Apps Script).
 *
 * So the first row we push lands on an empty sheet → Apps Script writes
 * new HEADERS, then our row. Subsequent rows just append.
 *
 * Also runs the backfill wider than round 1 — scans EVERY Lavin convo
 * with activity since 2026-11-15 20:00 UTC, dispatches lead_captured
 * for any with product + (address OR phone). Pacing: 300ms sleep between
 * pushes so Apps Script's free-tier rate limit doesn't throttle us.
 */

use App\Jobs\PushSalesConnectorRow;
use App\Models\Conversation;
use App\Services\SalesConnectors\SalesConnectors;

const LAVIN_TEAM_ID      = 60;
const BACKFILL_SINCE_UTC = '2026-11-15 20:00:00';
const SLEEP_MICROS       = 300_000; // 300 ms between pushes

echo "Rebuilding Lavin sheet from empty state...\n\n";

$since = Carbon\Carbon::parse(BACKFILL_SINCE_UTC, 'UTC');

$convs = Conversation::whereHas('page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('last_message_at', '>=', $since)
    ->with(['contact', 'page', 'messages'])
    ->orderBy('last_message_at') // chronological so sheet reads top-down
    ->get();

echo "scanning " . $convs->count() . " conversations since " . $since->format('Y-m-d H:i') . " UTC\n";

$pushed = 0;
$skipped = [];

foreach ($convs as $c) {
    $captured = (array) ($c->captured_data ?? []);

    $hasProduct = ! empty($captured['product']);
    $hasContact = ! empty($captured['address']) || ! empty($captured['phone']);

    // AI order-confirmation detection (same wider patterns as round 3)
    $aiConfirmed = $c->messages
        ->where('sender_type', 'ai')
        ->where('direction', 'outbound')
        ->contains(function ($m) {
            $patterns = ['سجلت الأوردر', 'الإجمالي', 'جنيه كاش', 'التوصيل خلال', 'هيوصلك خلال'];
            foreach ($patterns as $p) {
                if (str_contains((string) $m->content, $p)) return true;
            }
            return false;
        });

    if (! $aiConfirmed && (! $hasProduct || ! $hasContact)) {
        continue; // not a lead
    }

    try {
        PushSalesConnectorRow::dispatchSync($c->id, SalesConnectors::EVENT_LEAD_CAPTURED);
        $pushed++;
        if ($pushed === 1) {
            echo "  first push dispatched — Apps Script will write new HEADERS as row 1\n";
        }
        if ($pushed % 5 === 0) {
            echo "  pushed {$pushed} so far...\n";
        }
        usleep(SLEEP_MICROS);
    } catch (\Throwable $e) {
        $skipped[] = "conv#{$c->id}: " . $e->getMessage();
    }
}

echo "\n=== DONE ===\n";
echo "pushed to sheet: {$pushed}\n";
echo "skipped: " . count($skipped) . "\n";
foreach ($skipped as $s) echo "  - {$s}\n";
