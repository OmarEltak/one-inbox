<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Livewire\Campaigns\WhatsAppWizard;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_flow_from_upload_to_launch(): void
    {
        Storage::fake('local');
        $team = Team::factory()->create(['features' => ['bulk_whatsapp_campaigns' => true]]);
        $user = User::factory()->create(['current_team_id' => $team->id]);
        Page::factory()->create([
            'team_id' => $team->id, 'platform' => 'whatsapp', 'is_active' => true,
        ]);

        $csv = "phone,name\n01026361218,Ahmed\n+971501234567,Fatima\n";
        $file = UploadedFile::fake()->createWithContent('list.csv', $csv);

        Livewire::actingAs($user)
            ->test(WhatsAppWizard::class)
            ->set('file', $file)
            ->call('advanceToMap')
            ->assertSet('step', 'map')
            ->set('phoneColumn', 'phone')
            ->set('nameColumn', 'name')
            ->set('defaultCountry', 'EG')
            ->call('advanceToCompose')
            ->assertSet('step', 'compose')
            ->set('campaignName', 'August Promo')
            ->set('body', 'Hi {{name}}, we have a deal for you')
            ->call('advanceToReview')
            ->assertSet('step', 'review')
            ->call('launch')
            ->assertSet('step', 'launched');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'August Promo', 'platform' => 'whatsapp', 'status' => 'active',
        ]);
        $this->assertDatabaseCount('campaign_recipients', 2);
    }

    public function test_wizard_requires_bulk_whatsapp_campaigns_feature(): void
    {
        $team = Team::factory()->create(['features' => []]);
        $user = User::factory()->create(['current_team_id' => $team->id]);

        // Mount should throw when feature flag is disabled.
        $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);
        $component = new WhatsAppWizard();
        $component->mount();
    }
}
