<?php
/**
 * Deep order sweep across ALL Lavin conversations ever (team 60).
 *
 * Finds EVERY conversation that looks like a real order, including:
 *   - captured_data has name + (phone OR address) + product
 *   - AI emitted "سجلت الأوردر"
 *   - OWNER sent a final-confirmation phrase externally (sender_type=user or external)
 *     containing price + address OR price + product
 *   - Customer explicitly asked to order with phone + address
 *
 * For each: extract best-guess data, dedupe by phone, report.
 */

use App\Models\Conversation;
use App\Models\Message;

$convs = Conversation::whereHas('page', fn ($q) => $q->where('team_id', 60))
    ->with(['contact', 'messages', 'page'])
    ->get();

echo "Scanning " . $convs->count() . " total Lavin convs\n\n";

function extractPhone(string $text): ?string {
    if (preg_match('/\b(01[0125]\d{8})\b/', $text, $m)) return $m[1];
    return null;
}

function extractOrderFromText(string $text): ?string {
    // Look for "شال كبير/صغير + color" combos
    if (preg_match_all('/شال\s*(كبير|صغير)\s*(?:×\s*\d+|\d+|)[^\n]{0,30}(أسود|اسود|بيج|برجندي|بني|نيبي)?/u', $text, $matches)) {
        return trim($matches[0][0] ?? '');
    }
    return null;
}

$candidates = [];

foreach ($convs as $c) {
    $cap = (array) ($c->captured_data ?? []);
    $msgs = $c->messages->sortBy('id');
    $allContent = $msgs->pluck('content')->implode("\n");

    // Signal 1: AI structured confirmation
    $hasAiConfirm = $msgs->contains(fn ($m) => $m->sender_type === 'ai' && str_contains((string) $m->content, 'سجلت الأوردر'));

    // Signal 2: captured_data has substantive fields
    $hasCapturedData = ! empty($cap['name']) || (! empty($cap['phone']) && ! empty($cap['address']));

    // Signal 3: owner (user/external) sent order-closing phrases
    $ownerClosed = $msgs->contains(function ($m) {
        if (! in_array($m->sender_type, ['user', 'external'])) return false;
        $c = (string) $m->content;
        return preg_match('/\d{2,4}\s*(جنيه|ج\.م|جم|جنية)|الإجمالي|تم الحجز|تم التأكيد|سأبعتهولك|هيوصلك/u', $c) === 1;
    });

    // Signal 4: customer sent phone + address keywords
    $customerText = $msgs->where('direction', 'inbound')->pluck('content')->implode(' ');
    $hasCustomerPhone = (bool) preg_match('/\b01[0125]\d{8}\b/', $customerText);
    $hasCustomerAddr = (bool) preg_match('/محافظ|عنوان|شارع|شقة|عمارة|مدينة|قرية|مركز|منطقة/u', $customerText);
    $hasOrderIntent = (bool) preg_match('/شال|عايزة|عاوزة|بحجز|اطلب|احجز|بشتري/u', $customerText);
    $strongCustomerSignal = $hasCustomerPhone && $hasCustomerAddr && $hasOrderIntent;

    $isOrder = $hasAiConfirm || $hasCapturedData || $ownerClosed || $strongCustomerSignal;
    if (! $isOrder) continue;

    // Extract best-guess data
    $phone = $cap['phone'] ?? extractPhone($customerText);
    $name = $cap['name'] ?? null;
    $address = $cap['address'] ?? null;
    $order = $cap['order_summary'] ?? extractOrderFromText($allContent);

    $candidates[] = [
        'conv' => $c, 'phone' => $phone, 'name' => $name, 'address' => $address, 'order' => $order,
        'in_sheet' => ! empty($cap['confirmed_at']),
        'reasons' => array_filter([
            $hasAiConfirm ? 'AI confirmed' : null,
            $hasCapturedData ? 'captured' : null,
            $ownerClosed ? 'owner closed externally' : null,
            $strongCustomerSignal ? 'customer gave phone+addr+intent' : null,
        ]),
    ];
}

echo "=== FOUND " . count($candidates) . " order candidates ===\n\n";

// Group by platform for clarity
$byPlatform = collect($candidates)->groupBy(fn ($c) => $c['conv']->platform);

foreach ($byPlatform as $platform => $group) {
    echo "\n### {$platform} — " . $group->count() . " candidates\n";
    foreach ($group as $x) {
        $c = $x['conv'];
        $inSheet = $x['in_sheet'] ? '✓ IN SHEET' : '✗ MISSING';
        echo sprintf(
            "  %s conv#%d paused=%s stage=%s | %s | phone=%s | order=%s | reason=%s\n",
            $inSheet, $c->id,
            $c->ai_paused ? 'Y' : 'n', $c->sales_stage,
            substr($x['name'] ?? '(no name)', 0, 25),
            $x['phone'] ?? '-',
            substr($x['order'] ?? '-', 0, 50),
            implode(' + ', $x['reasons'])
        );
    }
}

// Save the candidate list to /tmp for manual review
file_put_contents('/tmp/lavin-order-candidates.json', json_encode(
    array_map(fn ($x) => ['conv_id' => $x['conv']->id, 'platform' => $x['conv']->platform, 'name' => $x['name'], 'phone' => $x['phone'], 'address' => $x['address'], 'order' => $x['order'], 'in_sheet' => $x['in_sheet'], 'reasons' => $x['reasons'], 'contact' => $x['conv']->contact?->name], $candidates),
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
));
echo "\n→ full list saved to /tmp/lavin-order-candidates.json\n";
