<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingMessage;
use App\Jobs\SendPlatformMessage;
use App\Models\AiConfig;
use App\Models\ConnectedAccount;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\Billing\AiCredits;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * End-to-end baseline trace for one synthetic inbound message. Runs the full
 * pipeline SYNCHRONOUSLY (QUEUE_CONNECTION=sync for this command only), with
 * SendPlatformMessage mocked via Bus::fake so no outbound HTTP is sent.
 *
 * Explicitly documents what IS exercised vs what is bypassed:
 *   EXERCISED:
 *     - webhook_logs INSERT
 *     - ProcessIncomingMessage::handle (page lookup, contact + conversation + message creates)
 *     - SendAiResponse::handle (NaraRouter generateResponse call = real HTTP to provider)
 *     - ScoreLeadJob::handle (NaraRouter scoreMessage call = real HTTP to provider)
 *     - SendPlatformMessage DISPATCH (job class constructor + payload serialization)
 *   BYPASSED:
 *     - Nginx + PHP-FPM entry (we skip the HTTP layer)
 *     - Signature verification middleware (we bypass the webhook endpoint)
 *     - Rate-limit middleware (`throttle:webhooks`)
 *     - SendPlatformMessage::handle (OUTBOUND to Meta Graph is mocked via Bus::fake)
 *     - Message retry behavior (sync execution has no `tries` loop)
 *
 * Run (prod):
 *   php artisan load-test:e2e-trace
 *   php artisan load-test:e2e-trace --cleanup
 */
class LoadTestE2eTrace extends Command
{
    protected $signature = 'load-test:e2e-trace
        {--cleanup : Delete the fixture and exit}';

    protected $description = 'Trace one synthetic inbound message end-to-end (outbound mocked). Prints per-stage timing.';

    private const TEAM_NAME = 'LoadTest E2E Sink';
    private const PAGE_PID  = 'LOADTEST_E2E_PAGE';

    public function handle(): int
    {
        if ($this->option('cleanup')) {
            return $this->cleanup();
        }

        // Force sync execution for everything in this process.
        config(['queue.default' => 'sync']);

        $this->warn('EXERCISED: webhook_logs INSERT, ProcessIncomingMessage, SendAiResponse (real NaraRouter), ScoreLeadJob (real NaraRouter)');
        $this->warn('BYPASSED : HTTP ingest, signature verify, throttle, SendPlatformMessage::handle (Bus::fake), retry loops');
        $this->newLine();

        [$team, $page, $ownerId] = $this->ensureFixture();
        $this->line("Fixture: team id={$team->id}, page id={$page->id}, owner user_id={$ownerId}");
        $this->newLine();

        // Intercept outbound send. Everything else runs normally in-process.
        Bus::fake([SendPlatformMessage::class]);
        DB::enableQueryLog();

        $senderId = 'LOADTEST_E2E_SENDER_' . bin2hex(random_bytes(4));
        $payload = [
            'object' => 'page',
            'entry'  => [[
                'id'        => $page->platform_page_id,
                'time'      => (int) (microtime(true) * 1000),
                'messaging' => [[
                    'sender'    => ['id' => $senderId],
                    'recipient' => ['id' => $page->platform_page_id],
                    'timestamp' => (int) (microtime(true) * 1000),
                    'message'   => [
                        'mid'  => 'e2e_mid_' . bin2hex(random_bytes(8)),
                        'text' => 'Hi, do you offer bulk pricing for 50 users?',
                    ],
                ]],
            ]],
        ];

        $this->line('Step 1: INSERT webhook_log...');
        $t0 = microtime(true);
        $log = WebhookLog::create([
            'platform'   => 'facebook',
            'event_type' => 'load-test.e2e',
            'payload'    => $payload,
        ]);
        $t1 = microtime(true);
        $this->line(sprintf('       done in %.3fs — webhook_log id=%d', $t1 - $t0, $log->id));
        $this->newLine();

        $this->line('Step 2: Dispatch ProcessIncomingMessage (sync — chains SendAiResponse + ScoreLeadJob)...');
        $pre = count(DB::getQueryLog());
        $t2 = microtime(true);
        try {
            ProcessIncomingMessage::dispatch($log->id);
            $t3 = microtime(true);
            $this->info(sprintf('       completed in %.3fs', $t3 - $t2));
        } catch (\Throwable $e) {
            $t3 = microtime(true);
            $this->error(sprintf('       FAILED after %.3fs: %s', $t3 - $t2, $e->getMessage()));
            return self::FAILURE;
        }

        $post = count(DB::getQueryLog());
        $queryCount = $post - $pre;
        $this->line(sprintf('       SQL queries executed: %d', $queryCount));
        $this->newLine();

        $this->line('Step 3: Verify outcome...');
        $log->refresh();
        $contact = Contact::where('team_id', $team->id)
            ->orderByDesc('id')->first();
        $conversation = Conversation::where('team_id', $team->id)
            ->orderByDesc('id')->first();
        $messages = $conversation
            ? Message::where('conversation_id', $conversation->id)->orderBy('id')->get()
            : collect();

        $this->line(sprintf('  webhook_log.processed       = %s', $log->processed ? 'true' : 'false'));
        $this->line(sprintf('  webhook_log.team_id         = %s', (string) $log->team_id));
        $this->line(sprintf('  contact created             = id=%s name=%s', $contact?->id, $contact?->name));
        $this->line(sprintf('  conversation created        = id=%s', $conversation?->id));
        $this->line(sprintf('  messages on conversation    = %d', $messages->count()));
        foreach ($messages as $m) {
            $this->line(sprintf(
                '    message id=%d  direction=%-8s sender_type=%-7s content=%s',
                $m->id, $m->direction, $m->sender_type, \Illuminate\Support\Str::limit((string) $m->content, 60)
            ));
        }

        $outbound = Bus::dispatched(SendPlatformMessage::class);
        $this->line(sprintf('  SendPlatformMessage dispatches captured = %d', $outbound->count()));
        foreach ($outbound as $job) {
            $this->line(sprintf('    captured job: SendPlatformMessage(messageId=%s)', (string) ($job->messageId ?? '?')));
        }

        $this->newLine();
        $this->info('End-to-end trace complete.');
        $this->line(sprintf(
            'Totals: webhook_log INSERT %.3fs + ProcessIncomingMessage %.3fs = %.3fs wall time, %d SQL queries, 1 outbound captured (NOT sent).',
            $t1 - $t0, $t3 - $t2, ($t1 - $t0) + ($t3 - $t2), $queryCount,
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{0: Team, 1: Page, 2: int}
     */
    private function ensureFixture(): array
    {
        $ownerId = (int) (User::query()->min('id') ?? 1);

        $team = Team::firstOrCreate(
            ['name' => self::TEAM_NAME],
            [
                'slug'              => 'loadtest-e2e-sink',
                'owner_id'          => $ownerId,
                'subscription_plan' => 'business',
                'ai_credits_used'   => 0,
                'ai_credits_limit'  => 999_999,
                'ai_enabled'        => true,
            ],
        );

        // Ensure gates pass: enable AI, grant credits so canDispatchAi() returns true.
        if (! $team->ai_enabled) {
            $team->update(['ai_enabled' => true]);
        }
        $credits = app(AiCredits::class);
        if ($credits->balance($team)->total() < 100) {
            $credits->grant(
                team: $team,
                amount: 1000,
                balanceType: \App\Models\AiCreditLedgerEntry::BALANCE_WALLET,
                reason: 'load_test_grant',
            );
        }

        $connectedAccount = ConnectedAccount::firstOrCreate(
            ['team_id' => $team->id, 'platform' => 'facebook', 'platform_user_id' => 'LOADTEST_E2E_FB_USER'],
            ['name' => 'LoadTest E2E', 'is_active' => true, 'connected_at' => now()],
        );

        $page = Page::firstOrCreate(
            ['platform' => 'facebook', 'platform_page_id' => self::PAGE_PID],
            [
                'connected_account_id' => $connectedAccount->id,
                'team_id'              => $team->id,
                'name'                 => 'LoadTest E2E Page',
                'is_active'            => true,
            ],
        );

        // Active 24/7 AI config so SendAiResponse gates pass.
        AiConfig::firstOrCreate(
            ['page_id' => $page->id],
            [
                'is_active'                   => true,
                'is_24_7'                     => true,
                'tone'                        => 'professional',
                'language'                    => 'en',
                'response_delay_min_seconds'  => 0,
                'response_delay_max_seconds'  => 1,
                'contact_ai_reply_cap'        => 100,
                'working_hours'               => [],
            ],
        );

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

        $pageIds = DB::table('pages')->where('team_id', $team->id)->pluck('id');
        $acDeleted = DB::table('ai_configs')->whereIn('page_id', $pageIds)->delete();
        $msgDeleted = DB::table('messages')
            ->whereIn('conversation_id', DB::table('conversations')->where('team_id', $team->id)->pluck('id'))
            ->delete();
        $convDeleted = DB::table('conversations')->where('team_id', $team->id)->delete();
        $contactDeleted = DB::table('contacts')->where('team_id', $team->id)->delete();
        $pageDeleted = DB::table('pages')->where('team_id', $team->id)->delete();
        $caDeleted = DB::table('connected_accounts')->where('team_id', $team->id)->delete();
        $whDeleted = DB::table('webhook_logs')
            ->where('event_type', 'load-test.e2e')
            ->delete();
        $ledgerDeleted = DB::table('ai_credit_ledger')->where('team_id', $team->id)->delete();
        $teamDeleted = DB::table('teams')->where('id', $team->id)->delete();

        $this->info("Deleted: {$acDeleted} ai_configs, {$msgDeleted} msgs, {$convDeleted} convs, {$contactDeleted} contacts, {$pageDeleted} pages, {$caDeleted} accounts, {$whDeleted} webhook_logs, {$ledgerDeleted} ledger, {$teamDeleted} teams");

        return self::SUCCESS;
    }
}
