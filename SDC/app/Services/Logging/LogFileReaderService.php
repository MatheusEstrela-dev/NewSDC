<?php

namespace App\Services\Logging;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Serviço para leitura e análise de arquivos de log
 * Suporta logs JSON e texto simples com filtros avançados
 */
class LogFileReaderService
{
    /**
     * Diretório de logs
     */
    protected string $logPath;

    /**
     * Tipos de log disponíveis (multiplos padroes por tipo)
     */
    protected array $logTypes = [
        'laravel' => ['laravel.log', 'laravel-*.log'],
        'events' => ['events.log', 'events-*.log'],
        'critical' => ['critical.log', 'critical-*.log'],
        'queries' => ['queries.log', 'queries-*.log'],
        'jobs' => ['jobs.log', 'jobs-*.log'],
    ];

    public function __construct()
    {
        $this->logPath = storage_path('logs');
    }

    /**
     * Lista todos os arquivos de log disponíveis
     */
    public function listLogFiles(?string $type = null): array
    {
        $files = [];

        if ($type && isset($this->logTypes[$type])) {
            $patterns = $this->logTypes[$type];
            foreach ((array) $patterns as $pattern) {
                $found = glob($this->logPath . '/' . $pattern);
                if ($found) {
                    $files = array_merge($files, $found);
                }
            }
        } else {
            foreach ($this->logTypes as $logType => $patterns) {
                foreach ((array) $patterns as $pattern) {
                    $typeFiles = glob($this->logPath . '/' . $pattern);
                    if ($typeFiles) {
                        foreach ($typeFiles as $file) {
                            $files[] = $file;
                        }
                    }
                }
            }
        }

        // Remove duplicatas
        $files = array_unique($files);

        // Ordena por data de modificação (mais recente primeiro)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return array_map(function ($file) {
            return [
                'path' => $file,
                'name' => basename($file),
                'size' => filesize($file),
                'size_human' => $this->formatBytes(filesize($file)),
                'modified' => Carbon::createFromTimestamp(filemtime($file))->toIso8601String(),
                'type' => $this->detectLogType(basename($file)),
            ];
        }, $files);
    }

    /**
     * Lê logs com filtros avançados
     */
    public function readLogs(array $filters = []): Collection
    {
        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : Carbon::now()->subDays(7)->startOfDay();

        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();

        $type = $filters['type'] ?? null;
        $level = $filters['level'] ?? null;
        $search = $filters['search'] ?? null;
        $limit = $filters['limit'] ?? 1000;
        $specificFile = $filters['file'] ?? null;

        // Busca arquivos de log no intervalo de datas ou o arquivo específico
        $logFiles = $this->getLogFilesInRange($startDate, $endDate, $type, $specificFile);

        $allLogs = collect();

        foreach ($logFiles as $logFile) {
            $logs = $this->parseLogFile($logFile['path']);
            $allLogs = $allLogs->merge($logs);
        }

        // Aplica filtros
        $filtered = $allLogs
            ->filter(function ($log) use ($startDate, $endDate, $level, $search) {
                // Filtro de data
                if (isset($log['timestamp'])) {
                    $logDate = Carbon::parse($log['timestamp']);
                    if ($logDate->lt($startDate) || $logDate->gt($endDate)) {
                        return false;
                    }
                }

                // Filtro de nível
                if ($level && isset($log['level']) && $log['level'] !== $level) {
                    return false;
                }

                // Filtro de busca
                if ($search) {
                    $haystack = json_encode($log);
                    if (stripos($haystack, $search) === false) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return $filtered;
    }

    /**
     * Busca arquivos de log no intervalo de datas
     */
    protected function getLogFilesInRange(Carbon $startDate, Carbon $endDate, ?string $type = null, ?string $file = null): array
    {
        if ($file) {
            $filePath = $this->logPath . '/' . $file;
            if (file_exists($filePath)) {
                return [
                    [
                        'path' => $filePath,
                        'name' => basename($filePath),
                    ]
                ];
            }
        }

        $allFiles = $this->listLogFiles($type);
        $relevantFiles = [];

        // Adiciona arquivos do intervalo de datas
        $currentDate = $startDate->copy();
        $datePatterns = [];
        
        while ($currentDate->lte($endDate)) {
            $datePatterns[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        foreach ($allFiles as $file) {
            $isHistorical = false;
            foreach ($datePatterns as $pattern) {
                if (str_contains($file['name'], $pattern)) {
                    $relevantFiles[] = $file;
                    $isHistorical = true;
                    break;
                }
            }

            // Arquivo base (sem data no nome, ex: laravel.log) contem os logs do dia atual.
            // So inclui se hoje estiver dentro do intervalo solicitado.
            if (!$isHistorical) {
                $cleanName = str_replace('.log', '', $file['name']);

                if (!preg_match('/\d{4}-\d{2}-\d{2}/', $cleanName)) {
                    $today = Carbon::today()->format('Y-m-d');
                    if (in_array($today, $datePatterns)) {
                        $relevantFiles[] = $file;
                    }
                }
            }
        }

        // Remove duplicatas e ordena por data de modificação (mais recentes primeiro)
        return collect($relevantFiles)
            ->unique('path')
            ->sortByDesc(function($file) {
                return filemtime($file['path']);
            })
            ->values()
            ->toArray();
    }

    /**
     * Faz parse de um arquivo de log
     */
    protected function parseLogFile(string $filePath): Collection
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return collect();
        }

        $fileSize = filesize($filePath);
        $maxReadBytes = 2 * 1024 * 1024; // 2MB max do final do arquivo
        $maxEntries = 500;

        $lines = $this->tailFile($filePath, $maxReadBytes);

        $logs = collect();
        $currentLog = null;

        foreach ($lines as $lineNumber => $line) {
            $jsonLog = $this->parseJsonLine($line);
            $laravelLog = $this->parseLaravelLogLine($line);

            if ($jsonLog || $laravelLog) {
                if ($currentLog) {
                    $this->finalizeLog($currentLog);
                    $logs->push($currentLog);
                }
                $currentLog = $jsonLog ?: $laravelLog;
                $currentLog['line'] = $lineNumber;
                $currentLog['file'] = basename($filePath);
                continue;
            }

            if ($currentLog) {
                $trimmedLine = trim($line);
                if ($trimmedLine !== '' && strlen($currentLog['message']) < 5000) {
                    $currentLog['message'] .= "\n" . $trimmedLine;
                }
            }
        }

        if ($currentLog) {
            $this->finalizeLog($currentLog);
            $logs->push($currentLog);
        }

        return $logs->take(-$maxEntries)->values();
    }

    /**
     * Le as ultimas linhas de um arquivo sem carregar tudo em memoria
     */
    protected function tailFile(string $filePath, int $maxBytes): array
    {
        $fileSize = filesize($filePath);
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return [];
        }

        $offset = max(0, $fileSize - $maxBytes);
        fseek($handle, $offset);

        if ($offset > 0) {
            fgets($handle);
        }

        $lines = [];
        $lineNumber = 0;
        while (($line = fgets($handle)) !== false) {
            $lines[++$lineNumber] = $line;
        }

        fclose($handle);

        return $lines;
    }

    /**
     * Parse de linha JSON
     */
    protected function parseJsonLine(string $line): ?array
    {
        $decoded = json_decode(trim($line), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Monolog format: dados estao em 'context'
        $ctx = $decoded['context'] ?? [];
        // ActivityLogger coloca metricas em context.data
        $data = $ctx['data'] ?? $decoded['data'] ?? [];

        // Normaliza level (Monolog usa numeros: 200=INFO, 300=WARNING, etc)
        $level = $decoded['level_name'] ?? $decoded['level'] ?? $decoded['severity'] ?? 'info';
        if (is_numeric($level)) {
            $levelMap = [100 => 'debug', 200 => 'info', 250 => 'notice', 300 => 'warning', 400 => 'error', 500 => 'critical', 550 => 'alert', 600 => 'emergency'];
            $level = $levelMap[$level] ?? 'info';
        }

        // Normaliza estrutura preservando campos importantes
        return [
            'timestamp' => $ctx['timestamp'] ?? $decoded['datetime'] ?? $decoded['timestamp'] ?? now()->toIso8601String(),
            'level' => strtolower($level),
            'message' => $decoded['message'] ?? $ctx['event_name'] ?? '',
            'context' => $ctx,
            'format' => 'json',

            // Campos do ActivityLogger (dentro de context)
            'class' => $ctx['class'] ?? $decoded['class'] ?? null,
            'method' => $ctx['method'] ?? $decoded['method'] ?? null,
            'file' => $ctx['file'] ?? $ctx['file_path'] ?? $decoded['file'] ?? null,
            'line' => $ctx['line'] ?? $decoded['line'] ?? null,
            'layer' => $ctx['layer'] ?? $decoded['layer'] ?? null,
            'url' => $ctx['url'] ?? $decoded['url'] ?? null,
            'http_method' => $ctx['http_method'] ?? $data['method'] ?? null,
            'ip_address' => $ctx['ip_address'] ?? $data['ip'] ?? null,
            'user_id' => $ctx['user_id'] ?? $data['user_id'] ?? null,
            'request_id' => $ctx['request_id'] ?? null,

            // Campos dentro de 'data' (metricas de requisicao)
            'data' => $data,
            'status_code' => $data['status_code'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? $ctx['time_ms'] ?? null,
            'route' => $data['route'] ?? null,

            // Campos de Slow Query (queries.log)
            'sql' => $ctx['sql'] ?? null,
            'time_ms' => $ctx['time_ms'] ?? null,
            'connection' => $ctx['connection'] ?? null,
        ];
    }

    /**
     * Finaliza o processamento de um log, extraindo JSON se necessário
     */
    protected function finalizeLog(array &$log): void
    {
        if ($log['format'] === 'laravel') {
            $message = $log['message'];
            $jsonObjects = $this->extractJsonObjects($message);
            
            foreach ($jsonObjects as $json) {
                $decoded = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $log['context'] = array_merge($log['context'], $decoded);
                    $message = str_replace($json, '', $message);
                }
            }

            $log['message'] = trim($message);
            $log['context']['full_message'] = $log['message'];
        }
    }

    /**
     * Extrai objetos JSON do texto usando um contador de chaves
     */
    protected function extractJsonObjects(string $text): array
    {
        $objects = [];
        $length = strlen($text);
        $stack = 0;
        $start = -1;

        for ($i = 0; $i < $length; $i++) {
            if ($text[$i] === '{') {
                if ($stack === 0) {
                    $start = $i;
                }
                $stack++;
            } elseif ($text[$i] === '}') {
                $stack--;
                if ($stack === 0 && $start !== -1) {
                    $objects[] = substr($text, $start, $i - $start + 1);
                    $start = -1;
                }
            }
        }

        return $objects;
    }

    /**
     * Parse de linha de log Laravel padrão
     * Formato: [2025-12-27 15:30:45] local.ERROR: Mensagem {context}
     */
    protected function parseLaravelLogLine(string $line): ?array
    {
        // Regex para log Laravel (suporta milissegundos opcionais e qualquer string de ambiente)
        // Formato: [2025-12-27 15:30:45] local.ERROR: Mensagem {context}
        // Formato: [2025-12-27 15:30:45.123] production.CRITICAL: Mensagem
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d+)?)\] (\w+)\.(\w+): (.*)/', $line, $matches)) {
            $timestamp = $matches[1];
            $environment = $matches[2];
            $level = strtolower($matches[3]);
            $message = $matches[4];

            return [
                'timestamp' => Carbon::parse($timestamp)->toIso8601String(),
                'level' => $level,
                'message' => trim($message),
                'context' => [
                    'environment' => $environment,
                ],
                'format' => 'laravel',
            ];
        }

        return null;
    }

    /**
     * Detecta tipo de log pelo nome do arquivo
     */
    protected function detectLogType(string $filename): string
    {
        foreach ($this->logTypes as $type => $patterns) {
            foreach ((array) $patterns as $pattern) {
                $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
                $regex = str_replace('\.\*', '.*', $regex);
                if (preg_match($regex, $filename)) {
                    return $type;
                }
            }
        }

        return 'unknown';
    }

    /**
     * Retorna os tipos de log disponiveis
     */
    public function getAvailableTypes(): array
    {
        return $this->logTypes;
    }

    /**
     * Formata bytes para formato legivel
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Obtém estatísticas dos logs
     */
    public function getStatistics(array $filters = [], ?Collection $logs = null): array
    {
        $logs = $logs ?? $this->readLogs($filters);

        $stats = [
            'total_logs' => $logs->count(),
            'by_level' => [],
            'by_hour' => [],
            'by_day' => [],
            'top_errors' => [],
            'error_rate' => 0,
        ];

        // Agrupa por nível
        $stats['by_level'] = $logs->groupBy('level')->map->count()->toArray();

        // Agrupa por hora
        $stats['by_hour'] = $logs->groupBy(function ($log) {
            return Carbon::parse($log['timestamp'])->format('Y-m-d H:00');
        })->map->count()->toArray();

        // Agrupa por dia
        $stats['by_day'] = $logs->groupBy(function ($log) {
            return Carbon::parse($log['timestamp'])->format('Y-m-d');
        })->map->count()->toArray();

        // Top erros
        $errorLogs = $logs->whereIn('level', ['error', 'critical', 'emergency']);
        $stats['top_errors'] = $errorLogs
            ->groupBy('message')
            ->map(function ($group) {
                return [
                    'message' => $group->first()['message'],
                    'count' => $group->count(),
                    'last_occurrence' => $group->first()['timestamp'],
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();

        // Taxa de erros
        if ($stats['total_logs'] > 0) {
            $errorCount = $errorLogs->count();
            $stats['error_rate'] = round(($errorCount / $stats['total_logs']) * 100, 2);
        }

        return $stats;
    }

    /**
     * Download de log completo
     */
    public function downloadLog(string $filename): ?array
    {
        $filePath = $this->logPath . '/' . $filename;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        return [
            'path' => $filePath,
            'name' => $filename,
            'content' => file_get_contents($filePath),
            'size' => filesize($filePath),
        ];
    }

    /**
     * Limpa logs antigos
     */
    public function cleanOldLogs(int $days = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($days);
        $deleted = 0;

        $files = $this->listLogFiles();

        foreach ($files as $file) {
            $fileDate = Carbon::createFromTimestamp(filemtime($file['path']));

            if ($fileDate->lt($cutoffDate)) {
                if (unlink($file['path'])) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
