<?php

declare(strict_types=1);

use App\Jobs\PushSalesConnectorRow;
use App\Livewire\Settings\AiConfig as AiConfigComponent;
use App\Models\AiConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\SalesConnectors\SalesConnectors;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

// AI Settings → Connectors: a row reaches the operator's sheet / webhook when
// the AI captures the required info or a contact is marked Converted.

beforeEach(function () {
    SalesConnectors::$resolver = fn (string $host) => match ($host) {
        'script.google.com' => ['142.250.0.1'],
        'internal.example'  => ['10.0.0.5'],
        default             => false,
    };

    [$this->user, $this->team] = makeUserWithTeam();
    $this->actingAs($this->user);
    $this->page = makeEmailPage($this->team);
    $this->page->update(['platform' => 'whatsapp', 'name' => 'Brandk']);

    $this->config = AiConfig::create([
        'team_id' => $this->team->id, 'page_id' => $this->page->id, 'is_active' => true,
        'is_24_7' => true, 'model' => 'auto', 'tone' => 'friendly', 'goal' => 'sales', 'company_info' => 'test',
        'sales_connectors' => ['sheet_url' => 'https://script.google.com/macros/s/abc/exec', 'events' => SalesConnectors::EVENTS],
    ]);

    $this->contact = Contact::create([
        'team_id' => $this->team->id, 'platform' => 'whatsapp', 'platform_user_id' => '2010', 'name' => 'Wagdy', 'phone' => '2010',
    ]);
    $this->conversation = Conversation::create([
        'team_id' => $this->team->id, 'page_id' => $this->page->id, 'contact_id' => $this->contact->id,
        'platform' => 'whatsapp', 'platform_conversation_id' => '2010', 'status' => 'open',
        'captured_data' => ['name' => 'Wagdy', 'address' => 'Nasr City'], 'last_message_at' => now(),
    ]);
    Message::create(['conversation_id' => $this->conversation->id, 'direction' => 'inbound', 'sender_type' => 'contact', 'content_type' => 'text', 'content' => 'عايز اطلب ٢']);
});

afterEach(fn () => SalesConnectors::$resolver = null);

test('only public https URLs are accepted', function () {
    expect(SalesConnectors::isAllowedUrl('https://script.google.com/macros/s/abc/exec'))->toBeTrue()
        ->and(SalesConnectors::isAllowedUrl('http://script.google.com/x'))->toBeFalse()
        ->and(SalesConnectors::isAllowedUrl('https://localhost/x'))->toBeFalse()
        ->and(SalesConnectors::isAllowedUrl('https://127.0.0.1/x'))->toBeFalse()
        ->and(SalesConnectors::isAllowedUrl('https://internal.example/x'))->toBeFalse()
        ->and(SalesConnectors::isAllowedUrl('https://unresolvable.example/x'))->toBeFalse();
});

test('completing a conversation queues one lead_captured row', function () {
    Bus::fake([PushSalesConnectorRow::class]);

    $this->conversation->complete('all_required_fields_captured');
    $this->conversation->complete('manual'); // already completed — no duplicate row

    Bus::assertDispatchedTimes(PushSalesConnectorRow::class, 1);
    Bus::assertDispatched(PushSalesConnectorRow::class, fn ($job) => $job->event === SalesConnectors::EVENT_LEAD_CAPTURED);
});

test('marking a contact Converted queues a deal_closed row; disabled events do not', function () {
    Bus::fake([PushSalesConnectorRow::class]);

    $this->contact->update(['lead_status' => 'converted']);
    Bus::assertDispatched(PushSalesConnectorRow::class, fn ($job) => $job->event === SalesConnectors::EVENT_DEAL_CLOSED);

    $this->config->update(['sales_connectors' => ['sheet_url' => 'https://script.google.com/x', 'events' => []]]);
    $this->contact->update(['lead_status' => 'hot']);
    $this->contact->update(['lead_status' => 'converted']);
    Bus::assertDispatchedTimes(PushSalesConnectorRow::class, 1);
});

test('the job posts headers + row to the sheet and records the delivery', function () {
    Http::fake(['script.google.com/*' => Http::response('ok')]);

    (new PushSalesConnectorRow($this->conversation->id, SalesConnectors::EVENT_LEAD_CAPTURED))->handle();

    Http::assertSent(fn ($req) => $req['headers'][2] === 'Contact'
        && $req['row'][1] === 'Required info captured'
        && $req['row'][2] === 'Wagdy'
        && str_contains($req['row'][8], 'Address: Nasr City')
        && $req['row'][9] === 'عايز اطلب ٢');
    expect($this->config->fresh()->sales_connectors['last_delivery']['ok'])->toBeTrue();
});

test('a failing endpoint is recorded, not retried', function () {
    Http::fake(['script.google.com/*' => Http::response('nope', 500)]);

    (new PushSalesConnectorRow($this->conversation->id, SalesConnectors::EVENT_DEAL_CLOSED))->handle();

    $delivery = $this->config->fresh()->sales_connectors['last_delivery'];
    expect($delivery['ok'])->toBeFalse()->and($delivery['error'])->toContain('HTTP 500');
});

test('the Connectors tab saves URLs, refuses private ones, and exports a CSV', function () {
    Livewire::test(AiConfigComponent::class)
        ->set('connector_webhook_url', 'https://internal.example/hook')
        ->call('saveConnectors')
        ->assertHasErrors('connector_webhook_url')
        ->set('connector_webhook_url', '')
        ->set('connector_events', ['deal_closed'])
        ->call('saveConnectors')
        ->assertHasNoErrors()
        ->assertDispatched('connectors-saved');

    expect($this->config->fresh()->sales_connectors['events'])->toBe(['deal_closed']);

    $this->conversation->update(['sales_stage' => Conversation::STAGE_COMPLETED]);
    Livewire::test(AiConfigComponent::class)->call('exportLeads')->assertFileDownloaded();
});
