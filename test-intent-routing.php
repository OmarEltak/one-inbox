<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Livewire\AiChat;
use App\Models\DeepAnalysis;
use App\Models\Team;
use App\Services\Ai\DeepAnalysisService;

echo str_repeat('=', 72) . "\n";
echo "INTENT ROUTING + CACHE SMOKE TEST\n";
echo str_repeat('=', 72) . "\n\n";

$cases = [
    // User's actual 2026-10-10 prompt that was broken
    ['prompt' => 'read and analyze the last 100 messages for mishkah i want u to give me each name for the last 100 chats and what action our moderator did with it and what would u do instead or how would u have handled it as a proffesional',
     'expect_mode' => DeepAnalysis::MODE_AGENT_AUDIT,
     'note' => "user's exact 2026-10-10 broken prompt"],

    // Classic agent audit phrasings (should still route to audit)
    ['prompt' => 'audit how our moderators handled last week',
     'expect_mode' => DeepAnalysis::MODE_AGENT_AUDIT,
     'note' => 'classic audit phrasing'],
    ['prompt' => 'how did our team respond to the last 50 customers',
     'expect_mode' => DeepAnalysis::MODE_AGENT_AUDIT,
     'note' => 'classic how-did-our-team'],

    // Theme summary intents (should stay customer_themes)
    ['prompt' => 'what are the top objections in my last 500 conversations',
     'expect_mode' => DeepAnalysis::MODE_CUSTOMER_THEMES,
     'note' => 'themes summary — should NOT route to audit'],
    ['prompt' => 'analyze the last 200 contacts and find patterns',
     'expect_mode' => DeepAnalysis::MODE_CUSTOMER_THEMES,
     'note' => 'patterns = themes'],

    // Per-contact review phrasings
    ['prompt' => 'give me each name and what the agent did',
     'expect_mode' => DeepAnalysis::MODE_AGENT_AUDIT,
     'note' => 'give me each name = per contact'],
    ['prompt' => 'what would you do instead for each conversation',
     'expect_mode' => DeepAnalysis::MODE_AGENT_AUDIT,
     'note' => 'what would you do instead = critique'],
    ['prompt' => 'list each contact one by one',
     'expect_mode' => DeepAnalysis::MODE_AGENT_AUDIT,
     'note' => 'one by one = per contact'],
];

// Use reflection to call the protected parseDeepAnalysisRequest method
$team = Team::find(2);

$chat = new class extends AiChat {
    public function callParse(Team $t, string $text): ?array {
        return $this->parseDeepAnalysisRequest($t, $text);
    }
};

$pass = 0; $fail = 0;
foreach ($cases as $c) {
    $result = $chat->callParse($team, $c['prompt']);
    if ($result === null) {
        echo "  ❌ FAIL (no routing)  — {$c['note']}\n";
        echo "    prompt: " . substr($c['prompt'], 0, 70) . "...\n";
        $fail++;
        continue;
    }
    $gotMode = $result['mode'] ?? 'NONE';
    $ok = $gotMode === $c['expect_mode'];
    $icon = $ok ? '✅' : '❌';
    echo "  $icon " . ($ok ? 'PASS' : 'FAIL') . " — {$c['note']}\n";
    echo "    expected mode: {$c['expect_mode']}  got: $gotMode\n";
    if (!empty($result['cohort_filter'])) {
        echo "    cohort_filter: " . json_encode($result['cohort_filter']) . "\n";
    }
    $ok ? $pass++ : $fail++;
}

echo "\n";
echo "── Cache-collision guard: agent_audit vs customer_themes must hash different ──\n";
$filter = ['page_id' => 33, 'limit' => 100];
$h1 = DeepAnalysisService::canonicalCohortHash($filter);
$h2 = DeepAnalysisService::canonicalCohortHash($filter);
echo ($h1 === $h2 ? '  ✅' : '  ❌') . " same filter → same hash (sanity check)\n";
$pass += ($h1 === $h2) ? 1 : 0;
$fail += ($h1 === $h2) ? 0 : 1;

echo "  (Mode is NOT part of the filter hash — it's a separate WHERE clause in\n";
echo "   findCachedAnalysis(). This test verifies the WHERE clause logic matches\n";
echo "   on mode so audit-intent query won't false-hit a stored themes result.)\n";

$service = app(DeepAnalysisService::class);
$seed = null;
try {
    \Illuminate\Support\Facades\DB::beginTransaction();
    $seed = DeepAnalysis::create([
        'team_id'              => $team->id,
        'triggered_by_user_id' => 3,
        'mode'                 => DeepAnalysis::MODE_CUSTOMER_THEMES,
        'cohort_filter'        => $filter,
        'cohort_size'          => 100,
        'credits_charged'      => 10,
        'status'               => DeepAnalysis::STATUS_COMPLETED,
        'started_at'           => now()->subMinutes(10),
        'completed_at'         => now()->subMinutes(10),
    ]);

    $hitThemes = $service->findCachedAnalysis($team, $filter, DeepAnalysis::MODE_CUSTOMER_THEMES);
    $missAudit = $service->findCachedAnalysis($team, $filter, DeepAnalysis::MODE_AGENT_AUDIT);
    $ok1 = $hitThemes?->id === $seed->id;
    $ok2 = $missAudit === null;
    echo ($ok1 ? '  ✅' : '  ❌') . " same mode → cache hit (themes stored, themes requested)\n";
    echo ($ok2 ? '  ✅' : '  ❌') . " different mode → cache MISS (themes stored, audit requested) — this was the bug\n";
    $pass += ($ok1 ? 1 : 0) + ($ok2 ? 1 : 0);
    $fail += ($ok1 ? 0 : 1) + ($ok2 ? 0 : 1);
} finally {
    \Illuminate\Support\Facades\DB::rollBack();
}

echo "\n" . str_repeat('=', 72) . "\n";
echo "RESULT: $pass passed, $fail failed\n";
echo str_repeat('=', 72) . "\n";
exit($fail > 0 ? 1 : 0);
