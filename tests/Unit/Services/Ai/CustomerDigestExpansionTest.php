<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Ai\AdminChatContext;

// Pins Phase C of the AI credit economy spec (§6): the default digest stays
// bounded at ~6 KB of inbound-only messages, while the targeted-page expansion
// mode blows the window out to ~30 KB and includes BOTH directions so the AI
// can audit moderator / agent replies against the customer's complaint.

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();

    $this->page = makeEmailPage($this->team, 'mishkah@example.com');
    $this->page->update(['platform' => 'facebook', 'name' => 'Mishkah University']);

    // 200 conversations, each with 20 messages (10 inbound + 10 outbound). The
    // volume blows past both the default and expanded limits so we can verify
    // the digests cut off at the right place.
    for ($i = 1; $i <= 200; $i++) {
        $contact = Contact::create([
            'team_id'          => $this->team->id,
            'platform'         => 'facebook',
            'platform_user_id' => "mishkah-{$i}",
            'name'             => "Student {$i}",
        ]);

        $conversation = Conversation::create([
            'team_id'                  => $this->team->id,
            'page_id'                  => $this->page->id,
            'contact_id'               => $contact->id,
            'platform'                 => 'facebook',
            'platform_conversation_id' => "mishkah-{$i}",
            'status'                   => 'open',
            'last_message_at'          => now()->subMinutes($i),
        ]);

        for ($j = 1; $j <= 10; $j++) {
            Message::create([
                'conversation_id' => $conversation->id,
                'direction'       => 'inbound',
                'sender_type'     => 'contact',
                'content_type'    => 'text',
                'content'         => "عايز اعرف مواعيد الكورس رقم {$j} student {$i}",
            ]);
            Message::create([
                'conversation_id' => $conversation->id,
                'direction'       => 'outbound',
                'sender_type'     => 'ai',
                'content_type'    => 'text',
                'content'         => "AGENT_REPLY_{$j} student {$i} شكرا لتواصلك معنا الكورس يبدأ يوم السبت",
            ]);
        }
    }
});

test('default digest returns at most ~6 KB of inbound-only messages', function () {
    $digest = app(AdminChatContext::class)->customerDigest($this->team->id, pageId: $this->page->id);

    expect(strlen($digest))->toBeLessThanOrEqual(6_500);
    expect($digest)->toContain('CUSTOMER CONVERSATIONS ON PAGE "Mishkah University"')
        ->not->toContain('EXPANDED CUSTOMER CONVERSATIONS')
        ->not->toContain('AGENT_REPLY_')
        ->toContain('عايز اعرف مواعيد الكورس');
});

test('expanded digest returns up to ~30 KB and includes both directions', function () {
    $digest = app(AdminChatContext::class)->customerDigest(
        $this->team->id,
        pageId: $this->page->id,
        expanded: true,
    );

    expect(strlen($digest))->toBeGreaterThan(10_000);
    expect(strlen($digest))->toBeLessThanOrEqual(32_000);
    expect($digest)
        ->toContain('EXPANDED CUSTOMER CONVERSATIONS ON PAGE "Mishkah University"')
        ->toContain('BOTH SIDES OF EACH THREAD')
        ->toContain('Customer: ')
        ->toContain('AI: ')
        ->toContain('AGENT_REPLY_')
        ->toContain('عايز اعرف مواعيد الكورس');
});

test('expanded digest still falls back to last-message preview for imported chats', function () {
    // A fresh page with only an imported conversation (no stored messages,
    // just last_message_preview).
    $importedPage = makeEmailPage($this->team, 'imported@example.com');
    $importedPage->update(['platform' => 'facebook', 'name' => 'Imported Page']);

    $contact = Contact::create([
        'team_id'          => $this->team->id,
        'platform'         => 'facebook',
        'platform_user_id' => 'imported-1',
        'name'             => 'Imported Fan',
    ]);
    Conversation::create([
        'team_id'                  => $this->team->id,
        'page_id'                  => $importedPage->id,
        'contact_id'               => $contact->id,
        'platform'                 => 'facebook',
        'platform_conversation_id' => 'imported-1',
        'status'                   => 'open',
        'last_message_at'          => now()->subMonths(2),
        'last_message_preview'     => 'عايزة اعرف سعر الدبلومة',
    ]);

    $digest = app(AdminChatContext::class)->customerDigest(
        $this->team->id,
        pageId: $importedPage->id,
        expanded: true,
    );

    expect($digest)
        ->toContain('EXPANDED CUSTOMER CONVERSATIONS ON PAGE "Imported Page"')
        ->toContain('(last-message preview, sender unknown) عايزة اعرف سعر الدبلومة')
        ->toContain('only their last-message preview is stored');
});
