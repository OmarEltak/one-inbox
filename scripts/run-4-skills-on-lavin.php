<?php
/**
 * Demo runner — exercises all 4 skills against Lavin Fashion (team 60) and
 * also fixes the conv#9427 row (real name + order extracted from chat).
 *
 * Run on prod:
 *   sudo -u www-data env XDG_CONFIG_HOME=/tmp HOME=/tmp \
 *     php artisan tinker --execute="require '/tmp/skills-demo.php';"
 */

use App\Services\Skills\Audit24h;
use App\Services\Skills\ConversationAuditor;
use App\Services\Skills\LiveConversationReplay;
use App\Services\Skills\OnboardingPlaybook;

const LAVIN_TEAM_ID = 60;

echo str_repeat('=', 60) . "\n";
echo "FIX: push conv#9427 with real data from chat review\n";
echo str_repeat('=', 60) . "\n\n";

$c9427 = \App\Models\Conversation::find(9427);
$c9427->update(['captured_data' => [
    'name'          => 'نرمين عبد العظيم',
    'phone'         => '01210225707',
    'address'       => 'الهانوفيل، إسكندرية',
    'order_summary' => '1 × شال صغير بني',
    'confirmed_at'  => now()->toIso8601String(),
    'note'          => 'confirmed manually by operator (أم بسمله WhatsApp display name)',
]]);
\App\Jobs\PushSalesConnectorRow::dispatchSync($c9427->id, \App\Services\SalesConnectors\SalesConnectors::EVENT_LEAD_CAPTURED);
echo "  conv#9427 → pushed with corrected data (name=نرمين عبد العظيم, order=شال صغير بني)\n\n";

echo str_repeat('=', 60) . "\n";
echo "SKILL 1 — ConversationAuditor (last 24h, Lavin)\n";
echo str_repeat('=', 60) . "\n\n";

$auditor = app(ConversationAuditor::class);
$violations = $auditor->audit(LAVIN_TEAM_ID, 24);

echo "total violations: " . $violations->count() . "\n";
foreach ($violations->groupBy('rule') as $rule => $group) {
    echo "  [{$rule}] × " . $group->count() . "\n";
}
echo "\ntop 5 (by severity):\n";
foreach ($violations->sortBy(fn ($v) => ['high' => 0, 'medium' => 1, 'low' => 2][$v['severity']] ?? 9)->take(5) as $v) {
    echo "  conv#{$v['conv_id']} msg#{$v['msg_id']} [{$v['severity']}] {$v['rule']}\n";
    echo "    @{$v['at']}  \"" . mb_substr($v['snippet'], 0, 100) . "\"\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "SKILL 2 — OnboardingPlaybook (validate Lavin's current config)\n";
echo str_repeat('=', 60) . "\n\n";

$playbook = new OnboardingPlaybook();
$cfg = \App\Models\AiConfig::find(17);
$currentProfile = [
    'business_description' => $cfg->business_description,
    'system_prompt'        => $cfg->system_prompt,
    'products'             => $cfg->product_catalog,
    'pricing_info'         => $cfg->pricing_info,
    'faq'                  => $cfg->faq,
    'escalation_keywords'  => $cfg->escalation_keywords,
    'required_fields'      => $cfg->required_capture_fields,
    'sheet_url'            => $cfg->sales_connectors['sheet_url'] ?? null,
    'webhook_url'          => $cfg->sales_connectors['webhook_url'] ?? null,
];
$warnings = $playbook->validate($currentProfile);
if (empty($warnings)) {
    echo "✅ config validation: PASS — nothing missing\n";
} else {
    echo "⚠️  config validation: " . count($warnings) . " warnings\n";
    foreach ($warnings as $w) echo "  - {$w}\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "SKILL 3 — LiveConversationReplay (conv#9452 — the 'عليكِ' bug)\n";
echo str_repeat('=', 60) . "\n\n";

// Replay conv#9452 with the CURRENT prompt (which now has Rule #7). If the
// prompt actually prevents the bug, the replayed reply should NOT contain
// "عليكِ" or "حلوين عليكِ" anymore.
$replay = app(LiveConversationReplay::class);
try {
    $result = $replay->replay(9452, $cfg->system_prompt);
    echo "context messages: {$result['context_size']}\n";
    echo "AI would now reply:\n";
    echo "  \"" . mb_substr($result['response'], 0, 400) . "\"\n\n";
    $hasForbidden = str_contains($result['response'], 'عليكِ')
        || str_contains($result['response'], 'عليكي')
        || str_contains($result['response'], 'شكلك');
    echo $hasForbidden
        ? "⚠️  REGRESSION — reply still contains forbidden body-flattery\n"
        : "✅ replay PASSES Rule #7 — no body-flattery in the new reply\n";
} catch (\Throwable $e) {
    echo "replay failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "SKILL 4 — Audit24h (full health report)\n";
echo str_repeat('=', 60) . "\n\n";

$audit = app(Audit24h::class);
$report = $audit->run(LAVIN_TEAM_ID, 48); // 48h to cover the trial window

echo "team: {$report['team']}  window: last {$report['window_h']}h\n\n";
echo "TRAFFIC\n";
echo "  conversations: {$report['traffic']['conversations']}\n";
echo "  inbound:       {$report['traffic']['inbound_msgs']}\n";
echo "  ai replies:    {$report['traffic']['ai_replies']}\n";
echo "  by platform:   " . json_encode($report['traffic']['by_platform']) . "\n\n";

echo "FUNNEL\n";
echo "  confirmed orders:      {$report['funnel']['confirmed_orders']}\n";
echo "  blocked confirmations: {$report['funnel']['blocked_confirmations']}\n";
echo "  conversion rate:       {$report['funnel']['conversion_pct']}%\n\n";

echo "VISION PIPELINE\n";
echo "  customer images:       {$report['vision']['customer_images']}\n";
echo "  with description:      {$report['vision']['with_description']}\n";
echo "  missing description:   {$report['vision']['missing_description']}\n";
echo "  success rate:          {$report['vision']['success_pct']}%\n\n";

echo "AI RULE VIOLATIONS\n";
echo "  total: {$report['ai_rule_violations']['total']}\n";
foreach ($report['ai_rule_violations']['by_rule'] as $rule => $n) {
    echo "  {$rule}: {$n}\n";
}

echo "\nDONE — all 4 skills executed against Lavin.\n";
