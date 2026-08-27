<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_queue_depths_json(): void
    {
        $response = $this->getJson('/health/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'queues' => ['urgent', 'default', 'campaigns'],
                'note',
            ]);
    }
}
