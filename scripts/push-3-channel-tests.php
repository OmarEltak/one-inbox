<?php
/**
 * Push 3 omar-test rows — one per channel — to prove sheet receives from
 * every platform. Uses REAL conversations as carriers (captured_data
 * snapshotted + restored so no customer data is polluted).
 */

use App\Jobs\PushSalesConnectorRow;
use App\Models\AiConfig;
use App\Models\Conversation;
use App\Services\SalesConnectors\SalesConnectors;

$tests = [
    [
        'label'   => 'test 1 — WhatsApp (single item)',
        'conv_id' => 9357, // Mazin, WA
        'captured' => [
            'name'          => 'omar test 1 (WA)',
            'phone'         => '01111111111',
            'address'       => 'القاهرة - التجمع الخامس - الحي الثامن',
            'order_summary' => '1 × شال كبير أسود',
        ],
    ],
    [
        'label'   => 'test 2 — Messenger (multi-item)',
        'conv_id' => 9404, // Emy, FB
        'captured' => [
            'name'          => 'omar test 2 (Messenger)',
            'phone'         => '01222222222',
            'address'       => 'الإسكندرية - سيدي جابر - شارع النبي دانيال',
            'order_summary' => '2 × شال كبير برجندي + 1 × شال صغير نيبي',
        ],
    ],
    [
        'label'   => 'test 3 — WhatsApp (3-item)',
        'conv_id' => 9354, // Amira, WA
        'captured' => [
            'name'          => 'omar test 3 (multi)',
            'phone'         => '01333333333',
            'address'       => 'الجيزة - 6 أكتوبر - الحي الثاني',
            'order_summary' => '3 × شال كبير بني + 2 × شال صغير بيج + 1 × شال كبير نيبي',
        ],
    ],
];

echo "=== Pushing 3 omar-test rows ===\n\n";

foreach ($tests as $t) {
    $conv = Conversation::find($t['conv_id']);
    if (! $conv) {
        echo $t['label'] . ": conv {$t['conv_id']} not found\n";
        continue;
    }

    $orig = $conv->captured_data;
    $conv->update(['captured_data' => $t['captured']]);

    try {
        PushSalesConnectorRow::dispatchSync($conv->id, SalesConnectors::EVENT_LEAD_CAPTURED);
        $cfg = AiConfig::where('page_id', $conv->page_id)->first()->fresh();
        $d = $cfg->sales_connectors['last_delivery'] ?? [];
        $ok = ($d['ok'] ?? false) ? 'OK' : 'FAILED';
        echo "{$t['label']} → page={$conv->page_id} platform={$conv->platform} delivery={$ok} at " . ($d['at'] ?? '?') . "\n";
    } catch (\Throwable $e) {
        echo "{$t['label']} → EXCEPTION " . $e->getMessage() . "\n";
    }

    $conv->update(['captured_data' => $orig]);
    usleep(500000);
}

echo "\n=== Row preview per test ===\n";
foreach ($tests as $t) {
    $conv = Conversation::find($t['conv_id']);
    $conv->setAttribute('captured_data', $t['captured']);
    $r = SalesConnectors::row($conv, 'lead_captured');
    echo "\n{$t['label']}:\n";
    echo "  Name: {$r['Name']} | Phone: {$r['Phone']} | Channel: {$r['Channel']}\n";
    echo "  Order: {$r['Order']}\n";
}
