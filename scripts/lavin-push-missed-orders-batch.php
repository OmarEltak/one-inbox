<?php
/**
 * Push 6 real missed orders for Lavin (team 60). Curated from deep-sweep +
 * individual chat inspection — these are confirmed orders where owner closed
 * externally OR AI engaged but didn't fully complete() the flow.
 *
 * NOT pushing:
 *   - conv#9381 شروق عبدالرحمن: customer cancelled ("الغي الاوردر")
 *   - conv#9460 عادل سمير: subscription-reminder spam
 *   - conv#9241 ريهام الجندي: no phone captured
 *   - conv#9314 مدام نور: no phone in chat (asked for WA contact)
 *   - conv#9210 Noha: owner never confirmed final order
 *   - conv#9357 Mazin: no name captured
 *   - conv#9433 Men3m: incomplete — customer said "ابعتلك تفاصيل" then stopped
 */

use App\Models\Conversation;
use App\Models\AiConfig;

$orders = [
    [
        'id' => 8939, 'name' => 'فرح مصطفى', 'phone' => '01113401325',
        'address' => 'القاهرة — حسن محمد بنك مصر، شارع وهبة الريفي، برج الراية',
        'order' => '1 × شال كبير بيج + 1 × شال كبير برجندي',
        'note' => 'Owner closed externally. Total 490 ج.م.',
    ],
    [
        'id' => 8944, 'name' => 'امنية احمد', 'phone' => '01016080007',
        'address' => 'الإسكندرية — ميامي الجديدة، شارع أبو بكر الصديق، متفرع...',
        'order' => '1 × شال كبير برجندي + 1 × شال كبير زيتي + 1 × شال كبير أسود',
        'note' => 'FB Messenger. Total 630 + 75 شحن. ⚠️ زيتي not in current catalog — owner agreed with customer externally.',
    ],
    [
        'id' => 8959, 'name' => 'ام بسمله', 'phone' => '01121542610',
        'address' => 'القاهرة — جسر السويس، آخر شارع الأربعين، عند معصرة أبو جمال',
        'order' => '1 × شال كبير بيج',
        'note' => 'FB Messenger. Total 220 + 60 شحن. Delivery requested Thursday.',
    ],
    [
        'id' => 9325, 'name' => '(غير محدد — راجع الشات)', 'phone' => '01021652285',
        'address' => 'القاهرة — الزيتون',
        'order' => '1 × شال كبير بني',
        'note' => 'AI confirmed order but never captured name. Owner asked later, customer didn\'t reply. Total 280 ج.م.',
    ],
    [
        'id' => 9387, 'name' => 'ام يوسف السروى', 'phone' => '01095220510',
        'address' => 'المنوفية — أشمون، عند مدرسة الحسين',
        'order' => '1 × شال كبير بيج + 1 × شال كبير أسود',
        'note' => 'Total 440 + 70 شحن = 510 ج.م. Customer asked delivery before Thursday.',
    ],
    [
        'id' => 9436, 'name' => 'باسم محسن', 'phone' => '01557887763',
        'address' => 'القاهرة — 5 درب القصر، متفرع من شارع البيباني',
        'order' => '4 × شال كبير نبيتي + 4 × شال كبير بني',
        'note' => '⚠️ Owner gave free shipping to <12-piece order (AI Rule #9 now forbids this). Total 1760 ج.م instead of 1825.',
    ],
];

$pushed = 0;
foreach ($orders as $o) {
    $c = Conversation::find($o['id']);
    if (! $c) { echo "  SKIP conv#{$o['id']} not found\n"; continue; }

    $c->update(['captured_data' => [
        'name'          => $o['name'],
        'phone'         => $o['phone'],
        'address'       => $o['address'],
        'order_summary' => $o['order'],
        'confirmed_at'  => now()->toIso8601String(),
        'note'          => $o['note'],
    ]]);

    // Direct dispatchSync — bypasses dedupe since we're explicitly pushing.
    \App\Jobs\PushSalesConnectorRow::dispatchSync(
        $c->id,
        \App\Services\SalesConnectors\SalesConnectors::EVENT_LEAD_CAPTURED
    );

    // Now stamp the sheet_pushed flag so future auto-fires don't re-push.
    $cap = $c->fresh()->captured_data;
    $cap['sheet_pushed_lead_captured_at'] = now()->toIso8601String();
    $c->update(['captured_data' => $cap]);

    $cfg = AiConfig::where('page_id', $c->page_id)->first()->fresh();
    $d = $cfg->sales_connectors['last_delivery'] ?? [];
    echo "  ✓ {$o['name']} → conv#{$c->id} ({$c->platform}) delivery=" . (($d['ok'] ?? false) ? 'OK' : 'FAILED') . "\n";
    $pushed++;
    usleep(400_000); // rate-limit Apps Script
}
echo "\nDONE — pushed {$pushed} missed orders.\n";
