<?php

declare(strict_types=1);

use App\Livewire\SuperAdmin\Customers;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Livewire\Livewire;

test('customers list shows AI replies used, pages and connection type', function () {
    [$owner, $team] = makeUserWithTeam();
    $team->update(['name' => 'Brandk', 'ai_credits_used' => 12]);

    $page = makeEmailPage($team);
    $page->update(['platform' => 'whatsapp', 'name' => 'Brandk WA']);
    $page->connectedAccount->update(['platform' => 'whatsapp', 'metadata' => ['gateway_mode' => 'wuzapi']]);

    $contact = Contact::create(['team_id' => $team->id, 'platform' => 'whatsapp', 'platform_user_id' => '1', 'name' => 'A']);
    $conv = Conversation::create([
        'team_id' => $team->id, 'page_id' => $page->id, 'contact_id' => $contact->id,
        'platform' => 'whatsapp', 'platform_conversation_id' => '1', 'status' => 'open',
    ]);
    foreach (range(1, 3) as $i) {
        Message::create(['conversation_id' => $conv->id, 'direction' => 'outbound', 'sender_type' => 'ai', 'content_type' => 'text', 'content' => "r{$i}"]);
    }

    $admin = \App\Models\User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(Customers::class)
        ->assertSee('Brandk WA')
        ->assertSee('WhatsApp QR')
        ->assertSee('12 / 100')
        ->assertSee('3 sent in the last 30 days')
        ->assertSee('AI replies (30 days)');
});
