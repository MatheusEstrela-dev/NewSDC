<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Health Check",
 *     description="Endpoints de monitoramento e saúde do sistema"
 * )
 */
class HealthCheckController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/health",
     *     summary="Health check básico",
     *     description="Verifica se a aplicação está respondendo",
     *     operationId="healthBasic",
     *     tags={"Health Check"},
     *     @OA\Response(
     *         response=200,
     *         description="Sistema operacional",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="uptime", type="integer", example=3600)
     *         )
     *     )
     * )
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'uptime' => $this->getUptime(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/health/detailed",
     *     summary="Health check detalhado",
     *     description="Verifica todos os componentes críticos do sistema (DB, Redis, Cache, Filas)",
     *     operationId="healthDetailed",
     *     tags={"Health Check"},
     *     @OA\Response(
     *         response=200,
     *         description="Status detalhado de todos componentes",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="healthy"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="checks", type="object",
     *                 @OA\Property(property="database", type="object",
     *                     @OA\Property(property="status", type="string", example="ok"),
     *                     @OA\Property(property="latency_ms", type="number", example=2.45)
     *                 ),
     *                 @OA\Property(property="redis", type="object",
     *                     @OA\Property(property="status", type="string", example="ok"),
     *                     @OA\Property(property="latency_ms", type="number", example=1.12)
     *                 ),
     *                 @OA\Property(property="cache", type="object",
     *                     @OA\Property(property="status", type="string", example="ok")
     *                 ),
     *                 @OA\Property(property="queue", type="object",
     *                     @OA\Property(property="status", type="string", example="ok"),
     *                     @OA\Property(property="pending_jobs", type="integer", example=15)
     *                 )
     *             ),
     *             @OA\Property(property="system", type="object",
     *                 @OA\Property(property="memory_usage_mb", type="number", example=128.5),
     *                 @OA\Property(property="cpu_load", type="array", @OA\Items(type="number"), example={0.5, 0.6, 0.7})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Sistema com problemas",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="degraded"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'docker_network' => $this->checkDockerNetwork(),
            'monitoring' => $this->checkMonitoring(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'system' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'cpu_load' => sys_getloadavg(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'performance' => [
                'uptime_seconds' => $this->getUptime(),
                'requests_per_minute' => $this->getRequestsPerMinute(),
                'avg_response_time_ms' => $this->getAverageResponseTime(),
                'error_rate_percent' => $this->getErrorRate(),
            ],
            'metrics' => [
                'cache_hit_rate' => $this->getCacheHitRate(),
                'database_connections' => $this->getDatabaseConnections(),
                'active_sessions' => $this->getActiveSessions(),
            ],
        ], $allHealthy ? 200 : 503);
    }

    /**
     * @OA\Get(
     *     path="/api/health/metrics",
     *     summary="Métricas do sistema",
     *     description="Retorna métricas em formato Prometheus",
     *     operationId="healthMetrics",
     *     tags={"Health Check"},
     *     @OA\Response(
     *         response=200,
     *         description="Métricas Prometheus",
     *         @OA\MediaType(
     *             mediaType="text/plain",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
    public function metrics(): \Illuminate\Http\Response
    {
        $metrics = $this->getPrometheusMetrics();

        return response($metrics, 200)
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }

    /**
     * Verifica conexão com banco de dados
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::select('SELECT 1');

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica conexão com Redis
     */
    private function checkRedis(): array
    {
        $start = microtime(true);

        try {
            Redis::ping();

            $info = Redis::info();

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'memory_used_mb' => round(($info['used_memory'] ?? 0) / 1024 / 1024, 2),
                'connected_clients' => $info['connected_clients'] ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica sistema de cache
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . now()->timestamp;
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value === 'test' ? 'ok' : 'error',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica sistema de filas
     */
    private function checkQueue(): array
    {
        try {
            $queues = ['critical', 'high', 'default', 'webhooks', 'low'];
            $pending = 0;

            foreach ($queues as $queue) {
                $pending += Redis::llen("queues:{$queue}");
            }

            return [
                'status' => 'ok',
                'driver' => config('queue.default'),
                'pending_jobs' => $pending,
                'queues_monitored' => $queues,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica espaço em disco
     */
    private function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $total = disk_total_space($storagePath);
            $free = disk_free_space($storagePath);
            $used = $total - $free;
            $usedPercent = round(($used / $total) * 100, 2);

            return [
                'status' => $usedPercent < 90 ? 'ok' : 'warning',
                'total_gb' => round($total / 1024 / 1024 / 1024, 2),
                'free_gb' => round($free / 1024 / 1024 / 1024, 2),
                'used_percent' => $usedPercent,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtém uptime do sistema
     */
    private function getUptime(): int
    {
        // Simples - em produção usar arquivo de controle
        return (int) Cache::remember('app_start_time', 3600, fn() => now()->timestamp);
    }

    /**
     * Obtém requisições por minuto
     */
    private function getRequestsPerMinute(): int
    {
        try {
            return (int) Redis::get('metrics:requests_per_minute') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtém tempo médio de resposta em ms
     */
    private function getAverageResponseTime(): float
    {
        try {
            $times = Redis::lrange('metrics:response_times', 0, 99); // Últimas 100 requisições
            if (empty($times)) {
                return 0.0;
            }
            $sum = array_sum(array_map('floatval', $times));
            return round($sum / count($times), 2);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Obtém taxa de erros em percentual
     */
    private function getErrorRate(): float
    {
        try {
            $totalRequests = (int) (Redis::get('metrics:total_requests') ?? 0);
            $totalErrors = (int) (Redis::get('metrics:total_errors') ?? 0);
            
            if ($totalRequests === 0) {
                return 0.0;
            }
            
            return round(($totalErrors / $totalRequests) * 100, 2);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Obtém taxa de acerto do cache em percentual
     */
    private function getCacheHitRate(): float
    {
        try {
            $hits = (int) (Redis::get('metrics:cache_hits') ?? 0);
            $misses = (int) (Redis::get('metrics:cache_misses') ?? 0);
            $total = $hits + $misses;
            
            if ($total === 0) {
                return 0.0;
            }
            
            return round(($hits / $total) * 100, 2);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Obtém número de conexões ativas do banco de dados
     */
    private function getDatabaseConnections(): array
    {
        try {
            $result = DB::select("SHOW STATUS WHERE Variable_name IN ('Threads_connected', 'Max_used_connections', 'Threads_running')");
            
            $connections = [];
            foreach ($result as $row) {
                $connections[strtolower($row->Variable_name)] = (int) $row->Value;
            }
            
            return [
                'active' => $connections['threads_connected'] ?? 0,
                'max_used' => $connections['max_used_connections'] ?? 0,
                'running' => $connections['threads_running'] ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'active' => 0,
                'max_used' => 0,
                'running' => 0,
            ];
        }
    }

    /**
     * Obtém número de sessões ativas
     */
    private function getActiveSessions(): int
    {
        try {
            // Se usar database para sessões
            if (config('session.driver') === 'database') {
                $table = config('session.table', 'sessions');
                // Conta apenas sessões não expiradas
                return DB::table($table)
                    ->where('last_activity', '>', now()->subMinutes(config('session.lifetime', 120))->timestamp)
                    ->count();
            }
            
            // Se usar Redis para sessões, tenta contar chaves
            if (config('session.driver') === 'redis') {
                try {
                    // Tenta usar KEYS (pode ser lento em produção, mas funciona)
                    $pattern = config('cache.prefix', 'laravel') . '*laravel_session*';
                    $keys = Redis::keys($pattern);
                    return count($keys);
                } catch (\Exception $e) {
                    // Se KEYS não funcionar, retorna 0
                    return 0;
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Verifica status da rede Docker e containers
     */
    private function checkDockerNetwork(): array
    {
        try {
            // Verifica se estamos em um container Docker
            if (!file_exists('/.dockerenv')) {
                return [
                    'status' => 'warning',
                    'message' => 'Não está rodando em container Docker',
                    'containers' => [],
                ];
            }

            // Detecta o nome da rede do container atual
            $containerName = gethostname();
            $networkName = $this->detectDockerNetwork($containerName);
            
            $containers = $this->getDockerContainers($networkName);

            // Verifica se todos os containers essenciais estão rodando
            $essentialContainers = ['app', 'db', 'redis', 'nginx'];
            $runningContainers = array_column($containers, 'name');
            $missingContainers = array_diff($essentialContainers, $runningContainers);

            $status = empty($missingContainers) ? 'ok' : 'warning';

            return [
                'status' => $status,
                'network_name' => $networkName,
                'containers' => $containers,
                'total_containers' => count($containers),
                'running_containers' => count(array_filter($containers, fn($c) => $c['status'] === 'running')),
                'missing_essential' => $missingContainers,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'containers' => [],
            ];
        }
    }

    /**
     * Detecta o nome da rede Docker do container atual
     */
    private function detectDockerNetwork(string $containerName): string
    {
        try {
            // Tenta obter a rede do container atual
            $command = "docker inspect -f '{{range \$net, \$conf := .NetworkSettings.Networks}}{{\$net}}{{end}}' {$containerName} 2>/dev/null";
            $network = trim(shell_exec($command) ?: '');
            
            if ($network) {
                return $network;
            }
            
            // Fallback: tenta encontrar rede com padrão comum
            $command = "docker network ls --format '{{.Name}}' | grep -E '(sdc|dev)' | head -1 2>/dev/null";
            $network = trim(shell_exec($command) ?: '');
            
            return $network ?: 'sdc-dev_sdc_network';
        } catch (\Exception $e) {
            return 'sdc-dev_sdc_network';
        }
    }

    /**
     * Obtém informações dos containers Docker
     */
    private function getDockerContainers(string $networkName): array
    {
        $containers = [];
        
        try {
            // Primeiro tenta buscar pela rede específica
            $command = "docker ps --filter network={$networkName} --format '{{.Names}}|{{.Status}}|{{.Image}}' 2>/dev/null";
            $output = shell_exec($command);
            
            // Se não encontrar, busca containers com prefixo sdc
            if (!$output || trim($output) === '') {
                $command = "docker ps --filter 'name=sdc' --format '{{.Names}}|{{.Status}}|{{.Image}}' 2>/dev/null";
                $output = shell_exec($command);
            }
            
            // Último fallback: todos os containers
            if (!$output || trim($output) === '') {
                $command = "docker ps --format '{{.Names}}|{{.Status}}|{{.Image}}' 2>/dev/null";
                $output = shell_exec($command);
            }

            if ($output && trim($output) !== '') {
                $lines = array_filter(explode("\n", trim($output)));
                
                foreach ($lines as $line) {
                    $parts = explode('|', $line);
                    if (count($parts) >= 3) {
                        $name = $parts[0];
                        $status = $parts[1];
                        $image = $parts[2];
                        
                        // Obtém IP do container
                        $ipCommand = "docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' {$name} 2>/dev/null";
                        $ip = trim(shell_exec($ipCommand) ?: '');
                        
                        // Se não encontrou IP, tenta buscar o primeiro IP disponível
                        if (empty($ip)) {
                            $ipCommand = "docker inspect -f '{{range \$key, \$value := .NetworkSettings.Networks}}{{if \$value.IPAddress}}{{\$value.IPAddress}}{{break}}{{end}}{{end}}' {$name} 2>/dev/null";
                            $ip = trim(shell_exec($ipCommand) ?: 'N/A');
                        }
                        
                        $ip = $ip ?: 'N/A';
                        
                        // Determina status
                        $containerStatus = str_contains(strtolower($status), 'up') ? 'running' : 'stopped';
                        
                        $containers[] = [
                            'name' => $name,
                            'status' => $containerStatus,
                            'image' => $image,
                            'ip' => $ip ?: 'N/A',
                            'status_raw' => $status,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Silencioso - pode não ter acesso ao Docker
        }

        return $containers;
    }

    /**
     * Verifica status dos serviços de monitoramento (Grafana e Prometheus)
     */
    private function checkMonitoring(): array
    {
        try {
            // Verifica diretamente via HTTP usando hostnames do Docker
            $grafanaStatus = $this->checkServiceHttp('grafana', 'http://grafana:3000/api/health');
            $prometheusStatus = $this->checkServiceHttp('prometheus', 'http://prometheus:9090/-/healthy');
            
            $allOnline = $grafanaStatus['online'] && $prometheusStatus['online'];
            
            return [
                'status' => $allOnline ? 'ok' : ($grafanaStatus['online'] || $prometheusStatus['online'] ? 'warning' : 'error'),
                'grafana' => $grafanaStatus,
                'prometheus' => $prometheusStatus,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'grafana' => ['online' => false, 'error' => 'Não verificado'],
                'prometheus' => ['online' => false, 'error' => 'Não verificado'],
            ];
        }
    }

    /**
     * Verifica serviço diretamente via HTTP (sem Docker CLI)
     */
    private function checkServiceHttp(string $name, string $url): array
    {
        $result = [
            'name' => $name,
            'online' => false,
            'latency_ms' => 0,
            'error' => null,
        ];

        try {
            // Verifica se cURL está disponível
            if (!function_exists('curl_init')) {
                $result['error'] = 'cURL não disponível';
                return $result;
            }

            // Tenta fazer requisição HTTP ao serviço
            $start = microtime(true);
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_NOBODY => true, // HEAD request apenas
            ]);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            $latency = round((microtime(true) - $start) * 1000, 2);
            
            if ($httpCode >= 200 && $httpCode < 400) {
                $result['online'] = true;
                $result['latency_ms'] = $latency;
            } else {
                $result['error'] = "HTTP {$httpCode}";
                if ($error) {
                    $result['error'] .= " - {$error}";
                }
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Gera métricas em formato Prometheus
     */
    private function getPrometheusMetrics(): string
    {
        $metrics = [];

        // Métricas de sistema
        $metrics[] = "# HELP sdc_up Sistema está online (1) ou offline (0)";
        $metrics[] = "# TYPE sdc_up gauge";
        $metrics[] = "sdc_up 1";

        $metrics[] = "# HELP sdc_memory_usage_bytes Uso de memória em bytes";
        $metrics[] = "# TYPE sdc_memory_usage_bytes gauge";
        $metrics[] = "sdc_memory_usage_bytes " . memory_get_usage(true);

        // Métricas de queue
        try {
            $queues = ['critical', 'high', 'default', 'webhooks', 'low'];
            $metrics[] = "# HELP sdc_queue_jobs_pending Jobs pendentes na fila";
            $metrics[] = "# TYPE sdc_queue_jobs_pending gauge";

            foreach ($queues as $queue) {
                $count = Redis::llen("queues:{$queue}");
                $metrics[] = "sdc_queue_jobs_pending{queue=\"{$queue}\"} {$count}";
            }
        } catch (\Exception $e) {
            // Silencioso
        }

        // Métricas de eventos (do ActivityLogger)
        try {
            $eventMetrics = \App\Services\Logging\ActivityLogger::getMetrics();

            $metrics[] = "# HELP sdc_events_total Total de eventos por tipo";
            $metrics[] = "# TYPE sdc_events_total counter";

            foreach ($eventMetrics as $metric) {
                $type = $metric['type'];
                $event = $metric['event'];
                $count = $metric['count'];
                $metrics[] = "sdc_events_total{type=\"{$type}\",event=\"{$event}\"} {$count}";
            }
        } catch (\Exception $e) {
            // Silencioso
        }

        return implode("\n", $metrics) . "\n";
    }
}
