<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Models\Page;
use App\Services\EvolutionApiService;
use App\Services\Wuzapi\SendResult;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WhatsAppSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_success_returns_ok_result_with_message_id(): void
    {
        $wuzapi = Mockery::mock(EvolutionApiService::class);
        $wuzapi->shouldReceive('sendText')
            ->once()
            ->with('inst-x', 'user-token', '201026361218', 'Hello!')
            ->andReturn('wa-msg-1');
        $this->app->instance(EvolutionApiService::class, $wuzapi);

        $page = Page::factory()->create([
            'platform'          => 'whatsapp',
            'platform_page_id'  => 'inst-x',
            'page_access_token' => encrypt('user-token'),
            'is_active'         => true,
        ]);

        $result = app(WhatsAppSender::class)->send($page, '+201026361218', 'Hello!');

        $this->assertInstanceOf(SendResult::class, $result);
        $this->assertTrue($result->sent);
        $this->assertSame('wa-msg-1', $result->providerMessageId);
    }

    public function test_send_null_return_is_treated_as_transient_failure(): void
    {
        $wuzapi = Mockery::mock(EvolutionApiService::class);
        $wuzapi->shouldReceive('sendText')->once()->andReturn(null);
        $this->app->instance(EvolutionApiService::class, $wuzapi);

        $page = Page::factory()->create([
            'platform'          => 'whatsapp',
            'platform_page_id'  => 'inst-x',
            'page_access_token' => encrypt('user-token'),
            'is_active'         => true,
        ]);

        $result = app(WhatsAppSender::class)->send($page, '+201026361218', 'Hi');

        $this->assertFalse($result->sent);
        $this->assertTrue($result->transient);
    }

    public function test_send_thrown_exception_is_treated_as_transient(): void
    {
        $wuzapi = Mockery::mock(EvolutionApiService::class);
        $wuzapi->shouldReceive('sendText')->once()->andThrow(new \RuntimeException('boom'));
        $this->app->instance(EvolutionApiService::class, $wuzapi);

        $page = Page::factory()->create([
            'platform'          => 'whatsapp',
            'platform_page_id'  => 'inst-x',
            'page_access_token' => encrypt('user-token'),
            'is_active'         => true,
        ]);

        $result = app(WhatsAppSender::class)->send($page, '+201026361218', 'Hi');

        $this->assertFalse($result->sent);
        $this->assertTrue($result->transient);
        $this->assertSame('boom', $result->error);
    }
}
