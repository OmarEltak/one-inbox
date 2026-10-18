<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DispatchDeepAnalysisJob;
use App\Models\ConnectedAccount;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeepAnalysis;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Creates an isolated test team with N fake conversations, dispatches M
 * DispatchDeepAnalysisJob instances, measures heavy-analysis queue drain.
 *
 * Each analysis against a cohort of ~50 conversations = 1 batch call to
 * NaraRouter (count=1 → aggregate short-circuits, no second call). Pure
 * measurement of per-analysis latency and (crucially) that the heavy-analysis
 * queue has only 1 worker — concurrent analyses serialize.
 *
 * Run (prod):
 *   php artisan load-test:deep-analysis-storm --concurrent=3
 *   php artisan load-test:deep-analysis-storm --cleanup
 */
class LoadTestDeepAnalysisStorm extends Command
{
    protected $signature = 'load-test:deep-analysis-storm
        {--concurrent=3 : Number of analyses to queue up at once}
        {--cohort=50 : Cohort size per analysis (<=100 avoids the chunk-aggregate second call)}
        {--watch-seconds=600 : Max seconds to watch heavy-analysis queue drain}
        {--cleanup : Delete all fixtures and exit}';

    protected $description = 'Flood the heavy-analysis queue with real Deep Analyses to measure concurrent throughput.';

    private const TEAM_NAME = 'LoadTest DeepAnalysis Sink';
    private const PAGE_SINK = 'LOADTEST_AI_DEEPANALYSIS';
    private const CONTACT_PREFIX = 'LOADTEST_DA_CONTACT_';

    public function handle(): int
    {
        if ($this->option('cleanup')) {
            return $this->cleanup();
        }

        $concurrent = (int) $this->option('concurrent');
        $cohort     = (int) $this->option('cohort');
        $watch      = (int) $this->option('watch-seconds');

        if ($concurrent < 1 || $concurrent > 20) {
            $this->error('concurrent must be between 1 and 20');

            return self::FAILURE;
        }

        [$team, $page, $ownerId] = $this->ensureFixture($cohort);
        $this->line("Test team id={$team->id}, page id={$page->id}, owner user_id={$ownerId}");

        $startDepth = (int) Redis::llen('queues:heavy-analysis');
        $this->line("heavy-analysis queue depth at start: {$startDepth}");
        $this->newLine();

        // Create N DeepAnalysis rows + dispatch jobs.
        $enqueuedIds = [];
        $enqueueStart = microtime(true);
        for ($i = 1; $i <= $concurrent; $i++) {
            $da = DeepAnalysis::create([
                'team_id'              => $team->id,
                'triggered_by_user_id' => $ownerId,
                'mode'                 => DeepAnalysis::MODE_CUSTOMER_THEMES,
                'cohort_filter'        => ['limit' => $cohort],
                'cohort_size'          => $cohort,
                'credits_charged'      => 0, // bypass billing — we aren't charging a real user
                'status'               => DeepAnalysis::STATUS_QUEUED,
            ]);
            DispatchDeepAnalysisJob::dispatch($da->id);
            $enqueuedIds[] = $da->id;
            $this->line("  enqueued DeepAnalysis id={$da->id}");
        }

        $this->newLine();
        $this->line(sprintf('Enqueued %d jobs in %.2fs. Watching drain...', $concurrent, microtime(true) - $enqueueStart));

        $watchStart = microtime(true);
        $firstCompletedAt = null;
        $lastCompletedAt  = null;

        while ((microtime(true) - $watchStart) < $watch) {
            sleep(2);
            $elapsed = microtime(true) - $watchStart;
            $depth = (int) Redis::llen('queues:heavy-analysis');
            $rows = DeepAnalysis::whereIn('id', $enqueuedIds)
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->get()
                ->pluck('c', 'status')
                ->toArray();

            $queued    = (int) ($rows[DeepAnalysis::STATUS_QUEUED] ?? 0);
            $running   = (int) ($rows[DeepAnalysis::STATUS_RUNNING] ?? 0);
            $completed = (int) ($rows[DeepAnalysis::STATUS_COMPLETED] ?? 0);
            $failed    = (int) ($rows[DeepAnalysis::STATUS_FAILED] ?? 0);

            $this->line(sprintf(
                '  t=%5.1fs  queue=%d  queued=%d running=%d completed=%d failed=%d',
                $elapsed,
                $depth,
                $queued,
                $running,
                $completed,
                $failed,
            ));

            if ($completed > 0 && $firstCompletedAt === null) {
                $firstCompletedAt = $elapsed;
            }
            if (($completed + $failed) >= $concurrent) {
                $lastCompletedAt = $elapsed;
                break;
            }
        }

        $this->newLine();

        // Per-analysis timing from DB.
        $analyses = DeepAnalysis::whereIn('id', $enqueuedIds)->get();
        $latencies = [];
        foreach ($analyses as $a) {
            if ($a->started_at && $a->completed_at) {
                $sec = $a->completed_at->diffInMicroseconds($a->started_at) / 1_000_000;
                $latencies[] = $sec;
                $this->line(sprintf(
                    '  analysis #%d: status=%-10s started_at=%s latency=%.2fs',
                    $a->id,
                    $a->status,
                    $a->started_at->toTimeString(),
                    $sec,
                ));
            } else {
                $this->line(sprintf('  analysis #%d: status=%s (no timing)', $a->id, $a->status));
            }
        }

        $this->newLine();
        if ($latencies) {
            sort($latencies);
            $n = count($latencies);
            $p = fn ($pct) => $latencies[(int) floor($n * $pct)] ?? $latencies[$n - 1];
            $this->info(sprintf(
                'Per-analysis latency: min=%.1fs p50=%.1fs p95=%.1fs max=%.1fs avg=%.1fs',
                min($latencies), $p(0.5), $p(0.95), max($latencies), array_sum($latencies) / $n,
            ));
        }
        if ($firstCompletedAt !== null && $lastCompletedAt !== null) {
            $this->info(sprintf(
                'First completion: t=%.1fs | Last completion: t=%.1fs | Spread: %.1fs',
                $firstCompletedAt, $lastCompletedAt, $lastCompletedAt - $firstCompletedAt,
            ));
        }

        return self::SUCCESS;
    }

    private function ensureFixture(int $cohort): array
    {
        $ownerId = (int) (User::query()->min('id') ?? 1);

        $team = Team::firstOrCreate(
            ['name' => self::TEAM_NAME],
            [
                'slug'              => 'loadtest-deepanalysis-sink',
                'owner_id'          => $ownerId,
                'subscription_plan' => 'business',
                'ai_credits_used'   => 0,
                'ai_credits_limit'  => 999_999,
            ],
        );

        $connectedAccount = ConnectedAccount::firstOrCreate(
            ['team_id' => $team->id, 'platform' => 'facebook', 'platform_user_id' => 'LOADTEST_FB_USER'],
            ['name' => 'LoadTest', 'is_active' => true, 'connected_at' => now()],
        );

        $page = Page::firstOrCreate(
            ['platform' => 'facebook', 'platform_page_id' => self::PAGE_SINK],
            [
                'connected_account_id' => $connectedAccount->id,
                'team_id'              => $team->id,
                'name'                 => 'LoadTest Sink Page',
                'is_active'            => true,
            ],
        );

        // Ensure cohort has enough conversations. Create missing ones.
        $existing = Conversation::where('team_id', $team->id)->count();
        $toCreate = max(0, $cohort - $existing);

        if ($toCreate > 0) {
            $this->line("Creating {$toCreate} fake conversations with messages...");
            for ($i = 1; $i <= $toCreate; $i++) {
                $contact = Contact::create([
                    'team_id'      => $team->id,
                    'name'         => self::CONTACT_PREFIX . $existing + $i,
                    'phone'        => self::CONTACT_PREFIX . ($existing + $i),
                    'lead_score'   => random_int(5, 50),
                    'lead_status'  => 'new',
                ]);

                $conv = Conversation::create([
                    'page_id'                    => $page->id,
                    'team_id'                    => $team->id,
                    'platform'                   => 'facebook',
                    'platform_conversation_id'   => 'LOADTEST_CONV_' . ($existing + $i),
                    'contact_id'                 => $contact->id,
                    'last_message_at'            => now()->subMinutes(random_int(1, 100)),
                ]);

                // 3 messages: inbound → outbound → inbound
                foreach ([
                    ['direction' => 'inbound',  'content' => 'Hi, do you offer bulk pricing for 100 users?'],
                    ['direction' => 'outbound', 'content' => 'Yes, we have a Business tier at $99/mo for up to 10k contacts.'],
                    ['direction' => 'inbound',  'content' => 'That sounds good. Can we schedule a demo next week?'],
                ] as $msg) {
                    Message::create([
                        'conversation_id' => $conv->id,
                        'direction'       => $msg['direction'],
                        'sender_type'     => $msg['direction'] === 'inbound' ? 'contact' : 'agent',
                        'content_type'    => 'text',
                        'content'         => $msg['content'],
                    ]);
                }
            }
        }

        return [$team, $page, $ownerId];
    }

    private function cleanup(): int
    {
        $team = Team::where('name', self::TEAM_NAME)->first();
        if (! $team) {
            $this->info('No fixture to clean up.');

            return self::SUCCESS;
        }

        $this->warn("Deleting fixture (team id={$team->id})...");

        $daDeleted   = DB::table('deep_analyses')->where('team_id', $team->id)->delete();
        $leadDeleted = DB::table('lead_events')
            ->whereIn('contact_id', DB::table('contacts')->where('team_id', $team->id)->pluck('id'))
            ->delete();
        $msgDeleted = DB::table('messages')
            ->whereIn('conversation_id', DB::table('conversations')->where('team_id', $team->id)->pluck('id'))
            ->delete();
        $convDeleted = DB::table('conversations')->where('team_id', $team->id)->delete();
        $contactDeleted = DB::table('contacts')->where('team_id', $team->id)->delete();
        $pageDeleted = DB::table('pages')->where('team_id', $team->id)->delete();
        $caDeleted = DB::table('connected_accounts')->where('team_id', $team->id)->delete();
        $teamDeleted = DB::table('teams')->where('id', $team->id)->delete();

        $this->info("Deleted: {$daDeleted} analyses, {$leadDeleted} lead_events, {$msgDeleted} messages, {$convDeleted} conversations, {$contactDeleted} contacts, {$pageDeleted} pages, {$caDeleted} accounts, {$teamDeleted} teams");

        return self::SUCCESS;
    }
}
