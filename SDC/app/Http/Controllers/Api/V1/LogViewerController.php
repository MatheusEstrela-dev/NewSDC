<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Logging\ActivityLogger;
use App\Services\Logging\LogFileReaderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Log Viewer V1",
 *     description="Sistema avançado de visualização de logs com filtros por data, tipo e nível"
 * )
 */
class LogViewerController extends Controller
{
    protected LogFileReaderService $logReader;

    public function __construct(LogFileReaderService $logReader)
    {
        $this->logReader = $logReader;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs",
     *     summary="Buscar logs com filtros avançados",
     *     description="Retorna logs filtrados por data, tipo, nível e termo de busca",
     *     operationId="logsSearch",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Data inicial (formato: Y-m-d ou Y-m-d H:i:s)",
     *         @OA\Schema(type="string", format="date-time", example="2025-12-20")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Data final (formato: Y-m-d ou Y-m-d H:i:s)",
     *         @OA\Schema(type="string", format="date-time", example="2025-12-27")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de log",
     *         @OA\Schema(type="string", enum={"laravel", "events", "critical", "queries", "jobs"})
     *     ),
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         description="Nível de severidade",
     *         @OA\Schema(type="string", enum={"debug", "info", "notice", "warning", "error", "critical", "alert", "emergency"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Termo de busca (busca em toda a mensagem e contexto)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de logs",
     *         @OA\Schema(type="integer", default=1000, maximum=5000)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de logs filtrados",
     *         @OA\JsonContent(
     *             @OA\Property(property="logs", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="filters", type="object"),
     *             @OA\Property(property="timestamp", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:laravel,events,critical,queries,jobs',
            'level' => 'nullable|in:debug,info,notice,warning,error,critical,alert,emergency',
            'search' => 'nullable|string|max:500',
            'limit' => 'nullable|integer|min:1|max:5000',
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::now()->subDays(7)->startOfDay();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();

        $logs = ActivityLogger::getLogsByDateRange(
            startDate: $startDate,
            endDate: $endDate,
            type: $validated['type'] ?? null,
            level: $validated['level'] ?? null,
            search: $validated['search'] ?? null,
            limit: $validated['limit'] ?? 1000,
            file: is_string($request->query('file')) ? $request->query('file') : null,
        );

        return response()->json([
            'logs' => $logs,
            'total' => count($logs),
            'filters' => [
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
                'type' => $validated['type'] ?? 'all',
                'level' => $validated['level'] ?? 'all',
                'search' => $validated['search'] ?? null,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/statistics",
     *     summary="Estatísticas de logs",
     *     description="Retorna estatísticas agregadas dos logs (total por nível, por hora, por dia, top erros)",
     *     operationId="logsStatistics",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Data inicial",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Data final",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de log",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas dos logs"
     *     )
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:laravel,events,critical,queries,jobs',
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::now()->subDays(7)->startOfDay();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();

        $stats = ActivityLogger::getLogStatistics(
            startDate: $startDate,
            endDate: $endDate,
            type: $validated['type'] ?? null,
            file: is_string($request->query('file')) ? $request->query('file') : null,
        );

        return response()->json([
            'statistics' => $stats,
            'period' => [
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/files",
     *     summary="Listar arquivos de log",
     *     description="Retorna lista de todos os arquivos de log disponíveis",
     *     operationId="logsFiles",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar por tipo de log",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de arquivos de log"
     *     )
     * )
     */
    public function files(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $files = $this->logReader->listLogFiles($type);

        return response()->json([
            'files' => $files,
            'total' => count($files),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/download/{filename}",
     *     summary="Download de arquivo de log",
     *     description="Faz download de um arquivo de log completo",
     *     operationId="logsDownload",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="filename",
     *         in="path",
     *         required=true,
     *         description="Nome do arquivo de log",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo de log",
     *         @OA\MediaType(
     *             mediaType="text/plain"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Arquivo não encontrado"
     *     )
     * )
     */
    public function download(string $filename)
    {
        // Validação de segurança: não permite path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/')) {
            return response()->json([
                'error' => 'Nome de arquivo inválido',
            ], 400);
        }

        $log = $this->logReader->downloadLog($filename);

        if (!$log) {
            return response()->json([
                'error' => 'Arquivo não encontrado',
            ], 404);
        }

        return Response::make($log['content'], 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $log['name'] . '"',
            'Content-Length' => $log['size'],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/recent",
     *     summary="Logs recentes (Redis)",
     *     description="Retorna logs recentes armazenados no Redis (tempo real)",
     *     operationId="logsRecentV1",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de log",
     *         @OA\Schema(type="string", enum={"all", "api", "webhook", "integration", "error", "performance", "security"}, default="all")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número de logs",
     *         @OA\Schema(type="integer", default=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs recentes do Redis"
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
            'source' => 'redis',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/logs/clean",
     *     summary="Limpar logs antigos",
     *     description="Remove arquivos de log mais antigos que o período especificado",
     *     operationId="logsClean",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Manter logs dos últimos N dias",
     *         @OA\Schema(type="integer", default=30, minimum=7, maximum=365)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs removidos com sucesso"
     *     )
     * )
     */
    public function clean(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:7|max:365',
        ]);

        $days = $validated['days'] ?? 30;
        $deleted = $this->logReader->cleanOldLogs($days);

        ActivityLogger::logEvent('system', 'logs_cleaned', [
            'days_kept' => $days,
            'files_deleted' => $deleted,
        ], Auth::id() !== null ? (string) Auth::id() : null, 'info');

        return response()->json([
            'message' => 'Logs antigos removidos com sucesso',
            'deleted' => $deleted,
            'days_kept' => $days,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/layers",
     *     summary="Camadas de log disponíveis",
     *     description="Retorna lista de camadas (layers) disponíveis para filtragem",
     *     operationId="logsLayers",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Camadas de log"
     *     )
     * )
     */
    public function layers(): JsonResponse
    {
        return response()->json([
            'layers' => [
                'api',
                'backend',
                'frontend',
                'system',
                'security',
                'database',
                'queue',
                'integration'
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/levels",
     *     summary="Níveis de log disponíveis",
     *     description="Retorna lista de níveis de severidade disponíveis",
     *     operationId="logsLevels",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Níveis de log"
     *     )
     * )
     */
    public function levels(): JsonResponse
    {
        return response()->json([
            'levels' => [
                ['value' => 'debug', 'label' => 'Debug', 'color' => 'gray'],
                ['value' => 'info', 'label' => 'Info', 'color' => 'blue'],
                ['value' => 'notice', 'label' => 'Notice', 'color' => 'cyan'],
                ['value' => 'warning', 'label' => 'Warning', 'color' => 'yellow'],
                ['value' => 'error', 'label' => 'Error', 'color' => 'orange'],
                ['value' => 'critical', 'label' => 'Critical', 'color' => 'red'],
                ['value' => 'alert', 'label' => 'Alert', 'color' => 'red'],
                ['value' => 'emergency', 'label' => 'Emergency', 'color' => 'red'],
            ],
            'types' => [
                ['value' => 'laravel', 'label' => 'Laravel'],
                ['value' => 'events', 'label' => 'Events'],
                ['value' => 'critical', 'label' => 'Critical'],
                ['value' => 'queries', 'label' => 'Queries'],
                ['value' => 'jobs', 'label' => 'Jobs'],
            ],
        ]);
    }
}
