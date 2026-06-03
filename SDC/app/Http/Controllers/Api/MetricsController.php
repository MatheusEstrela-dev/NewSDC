<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Database\ConnectionSemaphore;
use App\Http\Controllers\Controller;
use App\Services\Database\DatabaseCircuitBreaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

/**
 * Endpoint Prometheus para metricas de resiliencia.
 * Formato text-based v0.0.4 (text/plain).
 */
class MetricsController extends Controller
{
    public function __invoke(
        ConnectionSemaphore $sem,
        DatabaseCircuitBreaker $cb,
    ): Response {
        $stateMap = ['closed' => 0, 'half-open' => 1, 'open' => 2];
        $cbState = $stateMap[$cb->state()] ?? 0;

        try {
            $global = (int) (Redis::get('rate_limit:global:per_second') ?? 0);
        } catch (\Throwable) {
            $global = 0;
        }

        $body = implode("\n", [
            '# HELP sdc_db_slots_active Active DB slots held via ConnectionSemaphore',
            '# TYPE sdc_db_slots_active gauge',
            "sdc_db_slots_active {$sem->active()}",
            '# HELP sdc_db_slots_limit Configured DB slot limit',
            '# TYPE sdc_db_slots_limit gauge',
            "sdc_db_slots_limit {$sem->limit()}",
            '# HELP sdc_db_circuit_breaker_state 0=closed, 1=half-open, 2=open',
            '# TYPE sdc_db_circuit_breaker_state gauge',
            "sdc_db_circuit_breaker_state {$cbState}",
            '# HELP sdc_rate_limit_global_current Current global rate (per-second window)',
            '# TYPE sdc_rate_limit_global_current gauge',
            "sdc_rate_limit_global_current {$global}",
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }
}
