<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignDispatcherIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_query_uses_status_scheduled_index_and_scans_few_rows(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('EXPLAIN gate is MySQL-only; run in CI or against local MySQL.');
        }

        $campaigns = Campaign::factory()->count(10)->create();
        foreach ($campaigns as $c) {
            CampaignRecipient::factory()->count(10_000)->create([
                'campaign_id'  => $c->id,
                'status'       => 'sent',
                'channel'      => 'whatsapp',
                'scheduled_at' => now()->subDay(),
            ]);
        }
        CampaignRecipient::factory()->count(50)->create([
            'campaign_id'  => $campaigns->first()->id,
            'status'       => 'pending',
            'channel'      => 'whatsapp',
            'scheduled_at' => now()->subMinute(),
        ]);

        $explain = DB::select("
            EXPLAIN
            SELECT id
            FROM campaign_recipients
            WHERE status = 'pending' AND scheduled_at <= NOW()
            ORDER BY scheduled_at
            LIMIT 50
        ");

        $row = (array) $explain[0];

        $this->assertSame('campaign_recipients_status_scheduled_idx', $row['key'] ?? null,
            'Dispatcher query MUST use the (status, scheduled_at) index.');
        $this->assertLessThan(200, (int) ($row['rows'] ?? 0),
            'Dispatcher query rows estimate must be < 200; got ' . ($row['rows'] ?? 'null'));
        $this->assertStringNotContainsString('filesort', strtolower((string) ($row['Extra'] ?? '')),
            'Dispatcher query MUST NOT require filesort.');
    }
}
