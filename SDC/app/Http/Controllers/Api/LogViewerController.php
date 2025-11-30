<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Logging\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Log Viewer",
 *     description="Visualização de logs e eventos do sistema em tempo real"
 * )
 */
class LogViewerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/logs/recent",
     *     summary="Logs recentes",
     *     description="Retorna os logs mais recentes do sistema",
     *     operationId="logsRecent",
     *     tags={"Log Viewer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de log (all, api, webhook, integration, error, performance, security)",
     *         @OA\Schema(type="string", enum={"all", "api", "webhook", "integration", "error", "performance", "security"}, default="all")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número de logs a retornar",
     *         @OA\Schema(type="integer", default=100, maximum=1000)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de logs",
     *         @OA\JsonContent(
     *             @OA\Property(property="logs", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="type", type="string")
     *         )
     *     )
     * )
     */
    public function recent(Request $request): JsonResponse
    {
        $type = $request->query('type', 'all');
        $limit = min((int) $request->query('limit', 100), 1000);

        $logs = ActivityLogger::getRecentLogs($type, $limit);

        return response()->json([
            'logs' => $logs,
            'total' => count($logs),
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/logs/metrics",
     *     summary="Métricas de logs",
     *     description="Retorna estatísticas agregadas dos logs",
     *     operationId="logsMetrics",
     *     tags={"Log Viewer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Métricas agregadas",
     *         @OA\JsonContent(
     *             @OA\Property(property="metrics", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="summary", type="object",
     *                 @OA\Property(property="total_events", type="integer"),
     *                 @OA\Property(property="events_by_type", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function metrics(): JsonResponse
    {
        $metrics = ActivityLogger::getMetrics();

        $summary = [
            'total_events' => array_sum(array_column($metrics, 'count')),
            'events_by_type' => [],
        ];

        foreach ($metrics as $metric) {
            $type = $metric['type'];
            if (!isset($summary['events_by_type'][$type])) {
                $summary['events_by_type'][$type] = 0;
            }
            $summary['events_by_type'][$type] += (int) $metric['count'];
        }

        return response()->json([
            'metrics' => $metrics,
            'summary' => $summary,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/logs/errors",
     *     summary="Logs de erros",
     *     description="Retorna apenas logs de erro (error, critical)",
     *     operationId="logsErrors",
     *     tags={"Log Viewer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de erros",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function errors(): JsonResponse
    {
        $logs = ActivityLogger::getRecentLogs('error', 200);

        return response()->json([
            'errors' => $logs,
            'total' => count($logs),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/logs/stream",
     *     summary="Stream de logs em tempo real",
     *     description="SSE (Server-Sent Events) para logs em tempo real",
     *     operationId="logsStream",
     *     tags={"Log Viewer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Stream de eventos",
     *         @OA\MediaType(
     *             mediaType="text/event-stream"
     *         )
     *     )
     * )
     */
    public function stream(Request $request)
    {
        return response()->stream(function () {
            $lastCheck = now();

            while (true) {
                // Verifica novos logs a cada segundo
                $logs = ActivityLogger::getRecentLogs('all', 10);

                foreach ($logs as $log) {
                    $timestamp = \Carbon\Carbon::parse($log['timestamp']);

                    if ($timestamp->greaterThan($lastCheck)) {
                        echo "data: " . json_encode($log) . "\n\n";
                        ob_flush();
                        flush();
                    }
                }

                $lastCheck = now();
                sleep(1);

                // Verifica se conexão ainda está ativa
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
