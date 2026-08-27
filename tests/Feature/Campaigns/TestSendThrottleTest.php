<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Models\Campaign;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Wuzapi\SendResult;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class TestSendThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('campaigns:test-send:1');
        RateLimiter::clear('campaigns:test-send:2');
    }

    public function test_first_five_test_sends_succeed_sixth_throttled(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $team->members()->attach($user, ['role' => 'admin']);

        $page = Page::factory()->create([
            'team_id' => $team->id,
            'platform' => 'whatsapp',
            'is_active' => true,
        ]);
        $campaign = Campaign::factory()->create([
            'team_id' => $team->id,
            'platform' => 'whatsapp',
            'sender_page_id' => $page->id,
            'message_template' => 'Hi {{name}}',
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldReceive('send')->times(5)->andReturn(SendResult::ok('id'));
        $this->app->instance(WhatsAppSender::class, $sender);

        $this->actingAs($user);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/campaigns/{$campaign->id}/test-send", [
                'phone' => '+201026361218',
                'name' => 'Test',
            ])->assertOk();
        }

        $this->postJson("/campaigns/{$campaign->id}/test-send", [
            'phone' => '+201026361218',
            'name' => 'Test',
        ])->assertStatus(429);
    }
}
