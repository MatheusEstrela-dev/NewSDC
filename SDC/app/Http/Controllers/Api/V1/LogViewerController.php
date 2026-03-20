<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Logging\ActivityLogger;
use App\Services\Logging\LogFileReaderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            file: $request->query('file')
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
            file: $request->query('file')
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
        ], auth()->id(), 'info');

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
     *     summary="Camadas de log disponiveis",
     *     description="Retorna lista de camadas (layers) detectadas nos logs reais + estaticas",
     *     operationId="logsLayers",
     *     tags={"Log Viewer V1"},
     *     @OA\Response(
     *         response=200,
     *         description="Camadas de log"
     *     )
     * )
     */
    public function layers(): JsonResponse
    {
        // Layers estaticos padrao (arquitetura Laravel)
        $staticLayers = [
            'Controller',
            'Service',
            'Repository',
            'Model',
            'Middleware',
            'Request',
            'Job',
            'Event',
            'Listener',
            'Command',
            'DTO',
            'Resource',
            'Policy',
            'System',
        ];

        // Busca layers dinamicos dos logs recentes
        $dynamicLayers = [];
        try {
            $recentLogs = $this->logReader->readLogs(['limit' => 500]);
            $dynamicLayers = $recentLogs
                ->pluck('layer')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            // Silencioso - usa apenas layers estaticos
        }

        // Merge e remove duplicatas
        $allLayers = array_values(array_unique(array_merge($staticLayers, $dynamicLayers)));
        sort($allLayers);

        return response()->json([
            'layers' => $allLayers,
            'static' => $staticLayers,
            'dynamic' => $dynamicLayers,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/docker",
     *     summary="Logs de containers Docker",
     *     description="Retorna logs de containers Docker via stdout/stderr",
     *     operationId="logsDocker",
     *     tags={"Log Viewer V1"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="container",
     *         in="query",
     *         description="Nome ou ID do container",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="tail",
     *         in="query",
     *         description="Numero de linhas do final",
     *         @OA\Schema(type="integer", default=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs do Docker"
     *     )
     * )
     */
    public function docker(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'container' => 'nullable|string|max:100',
            'tail' => 'nullable|integer|min:1|max:5000',
        ]);

        $container = $validated['container'] ?? env('DOCKER_CONTAINER_NAME', 'sdc-app');
        $tail = $validated['tail'] ?? 100;

        // Verifica se Docker esta disponivel
        if (!$this->isDockerAvailable()) {
            return response()->json([
                'error' => 'Docker nao disponivel neste ambiente',
                'environment' => config('app.env'),
                'logs' => [],
            ], 200);
        }

        try {
            $logs = $this->getDockerLogs($container, $tail);

            return response()->json([
                'logs' => $logs,
                'total' => count($logs),
                'container' => $container,
                'source' => 'docker',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar logs do Docker',
                'message' => $e->getMessage(),
                'logs' => [],
            ], 200);
        }
    }

    /**
     * Verifica se Docker CLI esta disponivel
     */
    private function isDockerAvailable(): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('where docker 2>nul', $output, $returnCode);
        } else {
            exec('which docker 2>/dev/null', $output, $returnCode);
        }
        return $returnCode === 0;
    }

    /**
     * Busca logs de um container Docker
     */
    private function getDockerLogs(string $container, int $tail): array
    {
        $command = sprintf(
            'docker logs --tail %d --timestamps %s 2>&1',
            $tail,
            escapeshellarg($container)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException(implode("\n", $output));
        }

        $logs = [];
        foreach ($output as $line) {
            $parsed = $this->parseDockerLogLine($line);
            if ($parsed) {
                $logs[] = $parsed;
            }
        }

        return array_reverse($logs);
    }

    /**
     * Parse de linha de log Docker
     */
    private function parseDockerLogLine(string $line): ?array
    {
        // Formato Docker: 2025-12-27T15:30:45.123456789Z <mensagem>
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T[\d:.]+Z)\s+(.+)$/s', $line, $matches)) {
            $message = $matches[2];

            // Tenta extrair JSON da mensagem
            $jsonData = json_decode($message, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return array_merge([
                    'timestamp' => $matches[1],
                    'source' => 'docker',
                ], $jsonData);
            }

            // Detecta nivel baseado em keywords
            $level = 'info';
            if (stripos($message, 'error') !== false || stripos($message, 'exception') !== false) {
                $level = 'error';
            } elseif (stripos($message, 'warning') !== false || stripos($message, 'warn') !== false) {
                $level = 'warning';
            } elseif (stripos($message, 'critical') !== false || stripos($message, 'fatal') !== false) {
                $level = 'critical';
            }

            return [
                'timestamp' => $matches[1],
                'level' => $level,
                'message' => $message,
                'source' => 'docker',
                'layer' => 'System',
            ];
        }

        return null;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logs/levels",
     *     summary="Níveis de log disponíveis",
     *     description="Retorna lista de níveis de severidade disponíveis",
     *     operationId="logsLevels",
     *     tags={"Log Viewer V1"},
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
