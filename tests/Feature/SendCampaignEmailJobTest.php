<?php

declare(strict_types=1);

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailSuppression;
use App\Services\Email\SmtpMailerFactory;
use App\Services\Email\TemplateRenderer;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\NullTransport;

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    $this->page = makeEmailPage($this->team);

    // Bind a noop mailer so tests don't try a real SMTP connection.
    $this->app->bind(SmtpMailerFactory::class, function () {
        return new class extends SmtpMailerFactory {
            public function make(\App\Models\Page $page): Mailer
            {
                return new Mailer(new NullTransport());
            }
        };
    });
});

function makeCampaign(array $overrides = []): Campaign
{
    $defaults = [
        'team_id'            => test()->team->id,
        'created_by'         => test()->user->id,
        'platform'           => 'email',
        'name'               => 'Test',
        'type'               => 'broadcast',
        'subject'            => 'Hi {{name}}',
        'message_template'   => 'Hello {{name}}, from us.',
        'sender_page_id'     => test()->page->id,
        'daily_cap'          => 200,
        'jitter_min_seconds' => 0,
        'jitter_max_seconds' => 0,
        'status'             => Campaign::STATUS_ACTIVE,
    ];
    return Campaign::create(array_merge($defaults, $overrides));
}

test('sends the email and marks recipient sent', function () {
    $campaign = makeCampaign();
    $recipient = CampaignRecipient::create([
        'campaign_id'  => $campaign->id,
        'email'        => 'alice@x.com',
        'name'         => 'Alice',
        'status'       => CampaignRecipient::STATUS_PENDING,
        'scheduled_at' => now(),
    ]);

    (new SendCampaignEmailJob($recipient->id))->handle(
        app(SmtpMailerFactory::class),
        app(TemplateRenderer::class),
    );

    $recipient->refresh();
    expect($recipient->status)->toBe(CampaignRecipient::STATUS_SENT);
    expect($recipient->sent_at)->not->toBeNull();
    expect($campaign->refresh()->sent_count)->toBe(1);
});

test('skips suppressed recipients and marks them unsubscribed', function () {
    $campaign = makeCampaign();
    EmailSuppression::create([
        'team_id' => $this->team->id,
        'email'   => 'blocked@x.com',
        'reason'  => EmailSuppression::REASON_UNSUBSCRIBED,
    ]);
    $recipient = CampaignRecipient::create([
        'campaign_id'  => $campaign->id,
        'email'        => 'blocked@x.com',
        'status'       => CampaignRecipient::STATUS_PENDING,
        'scheduled_at' => now(),
    ]);

    (new SendCampaignEmailJob($recipient->id))->handle(
        app(SmtpMailerFactory::class),
        app(TemplateRenderer::class),
    );

    expect($recipient->refresh()->status)->toBe(CampaignRecipient::STATUS_UNSUBSCRIBED);
    expect($campaign->refresh()->sent_count)->toBe(0);
    expect($campaign->unsubscribed_count)->toBe(1);
});

test('does not send when campaign is paused', function () {
    $campaign = makeCampaign(['status' => Campaign::STATUS_PAUSED]);
    $recipient = CampaignRecipient::create([
        'campaign_id'  => $campaign->id,
        'email'        => 'x@x.com',
        'status'       => CampaignRecipient::STATUS_PENDING,
        'scheduled_at' => now(),
    ]);

    (new SendCampaignEmailJob($recipient->id))->handle(
        app(SmtpMailerFactory::class),
        app(TemplateRenderer::class),
    );

    expect($recipient->refresh()->status)->toBe(CampaignRecipient::STATUS_PENDING);
});

test('marks recipient failed after max attempts on send error', function () {
    // Replace factory with one that throws.
    $this->app->bind(SmtpMailerFactory::class, function () {
        return new class extends SmtpMailerFactory {
            public function make(\App\Models\Page $page): Mailer
            {
                throw new \RuntimeException('SMTP boom');
            }
        };
    });

    $campaign = makeCampaign();
    $recipient = CampaignRecipient::create([
        'campaign_id'  => $campaign->id,
        'email'        => 'x@x.com',
        'attempts'     => SendCampaignEmailJob::MAX_ATTEMPTS - 1,
        'status'       => CampaignRecipient::STATUS_PENDING,
        'scheduled_at' => now(),
    ]);

    (new SendCampaignEmailJob($recipient->id))->handle(
        app(SmtpMailerFactory::class),
        app(TemplateRenderer::class),
    );

    $recipient->refresh();
    expect($recipient->status)->toBe(CampaignRecipient::STATUS_FAILED);
    expect($recipient->last_error)->toContain('SMTP boom');
    expect($campaign->refresh()->failed_count)->toBe(1);
});
