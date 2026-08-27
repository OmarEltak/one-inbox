<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Campaigns\QueueDepthProbe;
use Illuminate\Http\JsonResponse;

/**
 * Observability endpoint — DOES NOT drive scheduler behavior.
 *
 * The scheduler measures queue depth in-process via QueueDepthProbe. Coupling
 * the scheduler to this endpoint would introduce a circular HTTP dependency.
 */
class HealthMetricsController extends Controller
{
    public function __invoke(QueueDepthProbe $probe): JsonResponse
    {
        return response()->json([
            'queues' => [
                'urgent'    => $probe->depthFor('urgent'),
                'default'   => $probe->depthFor('default'),
                'campaigns' => $probe->depthFor('campaigns'),
            ],
            'note' => 'Observability only — the scheduler does NOT read this endpoint.',
        ]);
    }
}
