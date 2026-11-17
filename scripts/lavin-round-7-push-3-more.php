<?php
/**
 * Round 7 — push 3 more completed orders that reached stage=completed via
 * the LLM extractor path but never got confirmed_at stamped. Data extracted
 * from AI messages in each conv.
 */

use App\Models\Conversation;
use App\Models\AiConfig;
use App\Jobs\PushSalesConnectorRow;
use App\Services\SalesConnectors\SalesConnectors;

$pushes = [
    [
        'conv_id'  => 9313,
        'contact'  => 'هبه محمد (originally called أم إيمان by AI, weird)',
        'captured' => [
            'name'          => 'هبه محمد',
            'phone'         => '01010038679',
            'address'       => 'الشرقية',
            'order_summary' => '1 × شال كبير أسود + 1 × شال كبير بيج + 1 × شال صغير بني',
            'confirmed_at'  => now()->toIso8601String(),
        ],
    ],
    [
        'conv_id'  => 9321,
        'contact'  => 'شريف محمد',
        'captured' => [
            'name'          => 'شريف محمد',
            'phone'         => '01210444608',
            'address'       => 'شبرا الخيمة',
            'order_summary' => '1 × شال كبير أسود + 1 × شال كبير بني',
            'confirmed_at'  => now()->toIso8601String(),
        ],
    ],
    [
        'conv_id'  => 9427,
        'contact'  => 'الصغير (name probably wrong — flagging for operator)',
        'captured' => [
            // Leaving name field but marking the captured data since the AI's
            // extractor put "الصغير" (the product size) as the name. Operator
            // should verify by reading the chat. Still pushing the row so it
            // shows in the sheet — row will have garbage name, operator sees
            // the gap.
            'name'          => '(⚠️ غير محدد — راجع الشات)',
            'phone'         => '01210225707',
            'address'       => 'الهانوفيل، إسكندرية',
            'order_summary' => '(غير محدد — راجع الشات)',
            'confirmed_at'  => now()->toIso8601String(),
        ],
    ],
];

foreach ($pushes as $p) {
    $c = Conversation::find($p['conv_id']);
    if (! $c) { echo "  conv#{$p['conv_id']} not found\n"; continue; }
    $c->update(['captured_data' => $p['captured']]);
    try {
        PushSalesConnectorRow::dispatchSync($c->id, SalesConnectors::EVENT_LEAD_CAPTURED);
        $cfg = AiConfig::where('page_id', $c->page_id)->first()->fresh();
        $d = $cfg->sales_connectors['last_delivery'] ?? [];
        $ok = ($d['ok'] ?? false) ? 'OK' : 'FAILED';
        echo "  {$p['contact']} → conv#{$c->id} delivery={$ok}\n";
    } catch (\Throwable $e) {
        echo "  {$p['contact']} → EXCEPTION: " . $e->getMessage() . "\n";
    }
    usleep(500_000);
}

echo "\nDONE.\n";
