<?php
/**
 * End-to-end smoke test for the Deep Analysis 24h cache + self-healing chain.
 * All test data in a transaction that's rolled back.
 */

require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Team;
use App\Models\DeepAnalysis;
use App\Services\Ai\DeepAnalysisService;
use App\Services\Ai\NaraRouterPool;
use Illuminate\Support\Facades\DB;

echo str_repeat('=', 72) . "\n";
echo "DEEP ANALYSIS + SELF-HEALING CHAIN SMOKE TEST\n";
echo str_repeat('=', 72) . "\n\n";

$pass = 0; $fail = 0;

$assert = function(string $name, bool $ok, string $details = '') use (&$pass, &$fail) {
    if ($ok) { echo "  ✅ $name\n"; $pass++; }
    else { echo "  ❌ $name\n"; if ($details) echo "     $details\n"; $fail++; }
};

echo "── 1. NaraRouterPool chain discovery ──\n";
$pool = new NaraRouterPool(
    baseUrl: (string) config('services.nararouter.base_url'),
    apiKey:  (string) config('services.nararouter.api_key'),
);
$text   = $pool->chainFor('text');
$vision = $pool->chainFor('vision');
$reason = $pool->chainFor('reasoning');

$assert('text chain has ≥ 1 model',       count($text) >= 1,   'count=' . count($text));
$assert('vision chain has ≥ 1 model',     count($vision) >= 1, 'count=' . count($vision));
$assert('reasoning chain has ≥ 1 model',  count($reason) >= 1, 'count=' . count($reason));
echo "    text:      " . implode(', ', $text) . "\n";
echo "    vision:    " . implode(', ', $vision) . "\n";
echo "    reasoning: " . implode(', ', $reason) . "\n\n";

echo "── 2. canonicalCohortHash is order-independent ──\n";
$h1 = DeepAnalysisService::canonicalCohortHash(['page_id' => 17, 'limit' => 100]);
$h2 = DeepAnalysisService::canonicalCohortHash(['limit' => 100, 'page_id' => 17]);
$h3 = DeepAnalysisService::canonicalCohortHash(['page_id' => 17, 'limit' => 50]);
$assert('same keys different order → same hash', $h1 === $h2, "h1=$h1  h2=$h2");
$assert('different value → different hash',      $h1 !== $h3, 'hashes equal');
echo "\n";

echo "── 3. findCachedAnalysis matches shape-equivalent filters ──\n";
DB::beginTransaction();

try {
    $team = Team::find(2);
    if (! $team) throw new RuntimeException('team 2 not found');

    $service = app(DeepAnalysisService::class);

    // Seed a 'completed' row 2h ago with filter A
    $filterA = ['page_id' => 17, 'limit' => 100];
    $filterA_reordered = ['limit' => 100, 'page_id' => 17];
    $filterB = ['page_id' => 17, 'limit' => 50];

    $seed = DeepAnalysis::create([
        'team_id'              => $team->id,
        'triggered_by_user_id' => 3,
        'mode'                 => DeepAnalysis::MODE_CUSTOMER_THEMES,
        'cohort_filter'        => $filterA,
        'cohort_size'          => 100,
        'credits_charged'      => 10,
        'status'               => DeepAnalysis::STATUS_COMPLETED,
        'result_json'          => ['test' => 'seed-row'],
        'started_at'           => now()->subHours(2),
        'completed_at'         => now()->subHours(2),
    ]);

    $hitSame      = $service->findCachedAnalysis($team, $filterA,           DeepAnalysis::MODE_CUSTOMER_THEMES);
    $hitReordered = $service->findCachedAnalysis($team, $filterA_reordered, DeepAnalysis::MODE_CUSTOMER_THEMES);
    $missDiff     = $service->findCachedAnalysis($team, $filterB,           DeepAnalysis::MODE_CUSTOMER_THEMES);
    $missWrongMode= $service->findCachedAnalysis($team, $filterA,           DeepAnalysis::MODE_AGENT_AUDIT ?? 'agent_audit');

    $assert('exact filter match returns the seeded row',    $hitSame?->id === $seed->id);
    $assert('reordered-keys filter matches the same row',   $hitReordered?->id === $seed->id);
    $assert('different filter does NOT match',              $missDiff === null);
    $assert('different mode does NOT match',                $missWrongMode === null);

    // Seed an OLD row (25h ago) and verify it is NOT a hit
    DeepAnalysis::create([
        'team_id'              => $team->id,
        'triggered_by_user_id' => 3,
        'mode'                 => DeepAnalysis::MODE_CUSTOMER_THEMES,
        'cohort_filter'        => $filterB,
        'cohort_size'          => 50,
        'credits_charged'      => 5,
        'status'               => DeepAnalysis::STATUS_COMPLETED,
        'result_json'          => ['test' => 'stale'],
        'started_at'           => now()->subHours(25),
        'completed_at'         => now()->subHours(25),
    ]);
    $missStale = $service->findCachedAnalysis($team, $filterB, DeepAnalysis::MODE_CUSTOMER_THEMES);
    $assert('row older than 24h is NOT a hit',              $missStale === null);

    // Seed a FAILED row and verify it is NOT a hit (even within 24h)
    $failed = DeepAnalysis::create([
        'team_id'              => $team->id,
        'triggered_by_user_id' => 3,
        'mode'                 => DeepAnalysis::MODE_CUSTOMER_THEMES,
        'cohort_filter'        => ['page_id' => 42, 'limit' => 10],
        'cohort_size'          => 10,
        'credits_charged'      => 5,
        'status'               => DeepAnalysis::STATUS_FAILED,
        'started_at'           => now()->subHour(),
        'completed_at'         => now()->subHour(),
        'error_message'        => 'test fail',
    ]);
    $missFailed = $service->findCachedAnalysis($team, ['page_id' => 42, 'limit' => 10], DeepAnalysis::MODE_CUSTOMER_THEMES);
    $assert('failed row is NOT a hit', $missFailed === null);
} finally {
    DB::rollBack();
    echo "\n    (test data rolled back)\n";
}

echo "\n" . str_repeat('=', 72) . "\n";
echo "RESULT: $pass passed, $fail failed\n";
echo str_repeat('=', 72) . "\n";
exit($fail > 0 ? 1 : 0);
