<?php
/**
 * Full 4-day Lavin audit — catches ANY conversation that looks like it could
 * be an order, and tells you whether it made it to the sheet.
 *
 * Signals for "this is an order":
 *   A. AI emitted "سجلت الأوردر" (strict, auto-flows to sheet)
 *   B. captured_data has name AND (phone OR address)
 *   C. owner (user/external) sent a final-confirmation phrase (الإجمالي,
 *      كاش عند الاستلام, تم الحجز, تم التأكيد)
 *   D. customer sent at least 2 of (phone pattern, address keywords like
 *      محافظة/عنوان/شارع, specific product request with color+size)
 *
 * For each matched conv, report:
 *   - in sheet? (captured_data.confirmed_at set)
 *   - why NOT in sheet if applicable (paused, missing field, LID failure etc.)
 *
 * Then verify the 5 complained names appear in the output.
 */

use App\Models\Conversation;
use App\Models\Message;

$since = Carbon\Carbon::parse('2026-11-14 00:00:00', 'UTC'); // last 4+ days

$convs = Conversation::whereHas('page', fn ($q) => $q->where('team_id', 60))
    ->where('last_message_at', '>=', $since)
    ->with(['contact', 'page', 'messages'])
    ->get();

echo "Scanning " . $convs->count() . " Lavin convos since 2026-11-14 UTC\n\n";

$orderSignals = [];

foreach ($convs as $c) {
    $cap = (array) ($c->captured_data ?? []);
    $msgs = $c->messages;

    // Signal A: AI structured confirmation
    $aiConfirmed = $msgs->contains(fn ($m) => $m->sender_type === 'ai' && str_contains((string) $m->content, 'سجلت الأوردر'));

    // Signal B: captured name + (phone OR address)
    $hasName    = ! empty($cap['name']);
    $hasPhone   = ! empty($cap['phone']);
    $hasAddress = ! empty($cap['address']);
    $capturedEnough = $hasName && ($hasPhone || $hasAddress);

    // Signal C: owner/external closed manually
    $humanClosed = $msgs->contains(function ($m) {
        if (! in_array($m->sender_type, ['user', 'external'])) return false;
        $c = (string) $m->content;
        return str_contains($c, 'الإجمالي')
            || str_contains($c, 'كاش عند الاستلام')
            || str_contains($c, 'تم الحجز')
            || str_contains($c, 'تم التأكيد')
            || (str_contains($c, 'جنيه') && str_contains($c, 'شحن'));
    });

    // Signal D: inbound evidence — customer typed phone + address-keywords
    $inboundText = $msgs->where('direction', 'inbound')->pluck('content')->implode(' ');
    $hasPhonePattern = (bool) preg_match('/\b01\d{9}\b/', $inboundText);
    $hasAddressKw = preg_match('/محافظ|عنوان|شارع|شقة|عمارة|مدينة/u', $inboundText) === 1;
    $hasProductAsk = preg_match('/شال.*(كبير|صغير).*(أسود|اسود|بيج|برجندي|بني|نيبي)/u', $inboundText) === 1
        || (str_contains($inboundText, 'بحجز') || str_contains($inboundText, 'عايزه') || str_contains($inboundText, 'عاوزه'));
    $strongInboundSignal = ($hasPhonePattern && $hasAddressKw) || ($hasProductAsk && ($hasPhonePattern || $hasAddressKw));

    $isOrder = $aiConfirmed || $capturedEnough || $humanClosed || $strongInboundSignal;
    if (! $isOrder) continue;

    $inSheet = ! empty($cap['confirmed_at']);

    $reason = [];
    if ($aiConfirmed) $reason[] = 'AI confirmed';
    if ($capturedEnough) $reason[] = 'captured ' . implode('+', array_filter(['name'=>$hasName?'name':null,'phone'=>$hasPhone?'phone':null,'addr'=>$hasAddress?'addr':null]));
    if ($humanClosed) $reason[] = 'owner closed manually';
    if ($strongInboundSignal) $reason[] = 'inbound order signals';

    $missingForSheet = [];
    if (! $hasName)    $missingForSheet[] = 'name';
    if (! $hasPhone)   $missingForSheet[] = 'phone';
    if (! $hasAddress) $missingForSheet[] = 'address';
    if (empty($cap['order_summary']) && ! $aiConfirmed) $missingForSheet[] = 'order_summary';

    $orderSignals[] = [
        'conv_id'  => $c->id,
        'platform' => $c->platform,
        'contact'  => $c->contact?->name ?? '-',
        'paused'   => (bool) $c->ai_paused,
        'stage'    => $c->sales_stage,
        'in_sheet' => $inSheet,
        'reason'   => implode(' / ', $reason),
        'missing'  => $missingForSheet,
        'cap_name' => $cap['name'] ?? '-',
        'cap_phone'=> $cap['phone'] ?? '-',
    ];
}

echo "=== FOUND " . count($orderSignals) . " convos with order signals ===\n\n";

$inSheet = array_filter($orderSignals, fn ($r) => $r['in_sheet']);
$notInSheet = array_filter($orderSignals, fn ($r) => ! $r['in_sheet']);

echo "IN SHEET: " . count($inSheet) . "\n";
foreach ($inSheet as $r) {
    echo sprintf("  ✓ conv#%d  %s  %s\n", $r['conv_id'], $r['cap_name'], $r['reason']);
}

echo "\nNOT IN SHEET: " . count($notInSheet) . "\n";
foreach ($notInSheet as $r) {
    $pausedTag = $r['paused'] ? '[PAUSED]' : '[AI active]';
    echo sprintf(
        "  ✗ conv#%d %s %s=%s/%s  reason: %s  missing: %s\n",
        $r['conv_id'],
        $pausedTag,
        $r['platform'],
        substr((string) $r['contact'], 0, 20),
        $r['cap_name'] === '-' ? '-' : substr($r['cap_name'], 0, 20),
        $r['reason'],
        implode(',', $r['missing'])
    );
}

// VERIFICATION: check the 5 complained names appeared in our list
echo "\n=== VERIFICATION — complained names coverage ===\n";
$complaintNames = ['اسراء محمود محمد', 'RoOokà', 'Omnia', 'aya reda', 'Marnona'];
foreach ($complaintNames as $n) {
    $found = collect($orderSignals)->filter(fn ($r) =>
        str_contains($r['contact'], $n) || str_contains($r['cap_name'], $n)
    );
    if ($found->isEmpty()) {
        echo "  ✗ \"{$n}\" — NOT in our scan (scan missed this!)\n";
    } else {
        $f = $found->first();
        echo "  ✓ \"{$n}\" — caught as conv#{$f['conv_id']} (in_sheet=" . ($f['in_sheet'] ? 'Y' : 'N') . ")\n";
    }
}
