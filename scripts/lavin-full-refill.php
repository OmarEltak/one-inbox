<?php
/**
 * Full sheet refill — user cleared the sheet and wants ALL confirmed orders
 * re-pushed in chronological order. Bypasses the lifetime dedupe by calling
 * PushSalesConnectorRow::dispatchSync directly (notify() has the dedupe guard;
 * the job itself does not).
 */

use App\Models\Conversation;
use App\Jobs\PushSalesConnectorRow;
use App\Services\SalesConnectors\SalesConnectors;

$convs = Conversation::whereHas('page', fn ($q) => $q->where('team_id', 60))
    ->whereNotNull('captured_data->confirmed_at')
    ->get()
    ->sortBy(fn ($c) => $c->captured_data['confirmed_at'] ?? '0000')
    ->values();

echo "Pushing " . $convs->count() . " confirmed Lavin orders in chronological order\n\n";

$pushed = 0;
foreach ($convs as $c) {
    $cap = $c->captured_data;
    $name = $cap['name'] ?? '-';
    try {
        PushSalesConnectorRow::dispatchSync($c->id, SalesConnectors::EVENT_LEAD_CAPTURED);
        $pushed++;
        echo sprintf("  ✓ %-30s conv#%-5d %-10s %s\n",
            mb_substr($name, 0, 28),
            $c->id,
            $c->platform,
            $cap['confirmed_at'] ?? '-'
        );
        usleep(350_000); // rate-limit Apps Script
    } catch (\Throwable $e) {
        echo "  ✗ FAIL conv#{$c->id} — " . $e->getMessage() . "\n";
    }
}

echo "\nDONE — {$pushed} rows pushed to Lavin sheet.\n";
