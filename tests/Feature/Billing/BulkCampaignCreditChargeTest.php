<?php

declare(strict_types=1);

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendCampaignWhatsAppJob;
use App\Models\AiCreditLedgerEntry;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\AiCredits;
use App\Services\Email\SmtpMailerFactory;
use App\Services\Email\TemplateRenderer;
use App\Services\Wuzapi\SendResult;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Phase RP (2026-10-06) — per-recipient credit charging for bulk campaigns.
 *
 * Covers:
 *   1. Successful WhatsApp send writes a `bulk_campaign_recipient` ledger row.
 *   2. Successful email send writes the same row.
 *   3. Idempotency key (campaign:{id}:recipient:{id}) prevents double charge
 *      when the job runs twice.
 *   4. If AiCredits::charge throws, the recipient still shows as sent
 *      (CLAUDE.md pin #5 — ledger failures must not undo real sends).
 */

function makeChargeTeam(int $monthly = 100): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'              => 'Charge Co',
        'slug'              => 'team-' . Str::random(8),
        'owner_id'          => $user->id,
        'subscription_plan' => 'starter',
    ]);
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();

    if ($monthly > 0) {
        app(AiCredits::class)->grant($team, $monthly, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    }

    return $team->fresh();
}

it('WhatsApp campaign send writes a bulk_campaign_recipient ledger row', function () {
    $team = makeChargeTeam(monthly: 10);
    $page = Page::factory()->create([
        'team_id'   => $team->id,
        'platform'  => 'whatsapp',
        'is_active' => true,
    ]);
    $campaign = Campaign::factory()->create([
        'team_id'          => $team->id,
        'status'           => 'active',
        'platform'         => 'whatsapp',
        'sender_page_id'   => $page->id,
        'message_template' => 'Hi {{name}}',
    ]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'channel'     => 'whatsapp',
        'phone'       => '+201026361218',
        'email'       => null,
        'status'      => 'queued',
    ]);

    $sender = Mockery::mock(WhatsAppSender::class);
    $sender->shouldReceive('send')->once()->andReturn(SendResult::ok('wa-1'));
    app()->instance(WhatsAppSender::class, $sender);

    (new SendCampaignWhatsAppJob($recipient->id))->handle($sender);

    expect($recipient->fresh()->status)->toBe('sent');

    $entry = AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', 'bulk_campaign_recipient')
        ->first();
    expect($entry)->not->toBeNull();
    expect((int) $entry->delta)->toBe(-1);
    expect($entry->cost_source_id)->toBe($campaign->id);
});

it('email campaign send writes a bulk_campaign_recipient ledger row', function () {
    $team = makeChargeTeam(monthly: 10);
    $page = Page::factory()->create([
        'team_id'   => $team->id,
        'platform'  => 'email',
        'is_active' => true,
    ]);
    $campaign = Campaign::factory()->create([
        'team_id'          => $team->id,
        'status'           => Campaign::STATUS_ACTIVE,
        'platform'         => 'email',
        'sender_page_id'   => $page->id,
        'subject'          => 'Hello',
        'message_template' => 'Hi {{name}}',
    ]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'channel'     => 'email',
        'phone'       => null,
        'email'       => 'target@example.com',
        'status'      => CampaignRecipient::STATUS_PENDING,
    ]);

    // Stub the mailer so we never hit SMTP. Anon-class subclasses avoid any
    // Mockery/Final-class edge case where the mock returns the real method.
    $factory = new class extends SmtpMailerFactory {
        public function __construct() {}
        public function senderAddress(\App\Models\Page $page): string
        {
            return 'sender@example.com';
        }
        public function make(\App\Models\Page $page): \Symfony\Component\Mailer\Mailer
        {
            return new \Symfony\Component\Mailer\Mailer(
                new \Symfony\Component\Mailer\Transport\NullTransport(),
            );
        }
    };

    $renderer = new class extends TemplateRenderer {
        public function __construct() {}
        public function render(string $subject, string $body, CampaignRecipient $r): array
        {
            return ['subject' => 'Hello', 'body' => 'Hi there'];
        }
    };

    (new SendCampaignEmailJob($recipient->id))->handle($factory, $renderer);

    expect($recipient->fresh()->status)->toBe(CampaignRecipient::STATUS_SENT);

    $entry = AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', 'bulk_campaign_recipient')
        ->first();
    expect($entry)->not->toBeNull();
    expect((int) $entry->delta)->toBe(-1);
    expect($entry->cost_source_id)->toBe($campaign->id);
});

it('idempotency key prevents a second ledger row when the job is replayed', function () {
    $team = makeChargeTeam(monthly: 10);
    $page = Page::factory()->create([
        'team_id'   => $team->id,
        'platform'  => 'whatsapp',
        'is_active' => true,
    ]);
    $campaign = Campaign::factory()->create([
        'team_id'          => $team->id,
        'status'           => 'active',
        'platform'         => 'whatsapp',
        'sender_page_id'   => $page->id,
        'message_template' => 'Hi',
    ]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'channel'     => 'whatsapp',
        'phone'       => '+201026361218',
        'email'       => null,
        'status'      => 'queued',
    ]);

    $sender = Mockery::mock(WhatsAppSender::class);
    $sender->shouldReceive('send')->twice()->andReturn(SendResult::ok('wa-1'));
    app()->instance(WhatsAppSender::class, $sender);

    // First dispatch — charges.
    (new SendCampaignWhatsAppJob($recipient->id))->handle($sender);
    // Force the recipient back to queued so the job runs again for the same key.
    $recipient->fresh()->update(['status' => 'queued', 'sent_at' => null]);
    // Second dispatch — must NOT write a new ledger row thanks to the idempotency key.
    (new SendCampaignWhatsAppJob($recipient->id))->handle($sender);

    $count = AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', 'bulk_campaign_recipient')
        ->count();
    expect($count)->toBe(1);
});

it('swallows a ledger failure — the message still shows as sent', function () {
    $team = makeChargeTeam(monthly: 10);
    $page = Page::factory()->create([
        'team_id'   => $team->id,
        'platform'  => 'whatsapp',
        'is_active' => true,
    ]);
    $campaign = Campaign::factory()->create([
        'team_id'          => $team->id,
        'status'           => 'active',
        'platform'         => 'whatsapp',
        'sender_page_id'   => $page->id,
        'message_template' => 'Hi',
    ]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'channel'     => 'whatsapp',
        'phone'       => '+201026361218',
        'email'       => null,
        'status'      => 'queued',
    ]);

    $sender = Mockery::mock(WhatsAppSender::class);
    $sender->shouldReceive('send')->once()->andReturn(SendResult::ok('wa-1'));
    app()->instance(WhatsAppSender::class, $sender);

    // Swap AiCredits for a mock that explodes on charge() but still answers
    // costFor() + balance() so the pre-send check lets the message through.
    $credits = Mockery::mock(AiCredits::class);
    $credits->shouldReceive('costFor')->with('bulk_campaign_recipient')->andReturn(1);
    $credits->shouldReceive('balance')->andReturn(new \App\Services\Billing\Balance(monthly: 10, wallet: 0));
    $credits->shouldReceive('charge')->once()->andThrow(new \RuntimeException('ledger down'));
    app()->instance(AiCredits::class, $credits);

    (new SendCampaignWhatsAppJob($recipient->id))->handle($sender);

    expect($recipient->fresh()->status)->toBe('sent');
});
