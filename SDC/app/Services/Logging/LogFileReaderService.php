<?php

namespace App\Services\Logging;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

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
     * Tipos de log disponíveis
     */
    protected array $logTypes = [
        'laravel' => 'laravel-*.log',
        'events' => 'events-*.log',
        'critical' => 'critical-*.log',
        'queries' => 'queries-*.log',
        'jobs' => 'jobs-*.log',
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
            $pattern = $this->logTypes[$type];
            $files = glob($this->logPath . '/' . $pattern);
        } else {
            foreach ($this->logTypes as $logType => $pattern) {
                $typeFiles = glob($this->logPath . '/' . $pattern);
                foreach ($typeFiles as $file) {
                    $files[] = $file;
                }
            }
        }

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

        // Adiciona arquivo atual sempre
        foreach ($allFiles as $file) {
            if (!str_contains($file['name'], '-2')) {
                $relevantFiles[] = $file;
            }
        }

        // Adiciona arquivos do intervalo de datas
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');

            foreach ($allFiles as $file) {
                if (str_contains($file['name'], $dateStr)) {
                    $relevantFiles[] = $file;
                }
            }

            $currentDate->addDay();
        }

        // Remove duplicatas
        return collect($relevantFiles)->unique('path')->values()->toArray();
    }

    /**
     * Faz parse de um arquivo de log
     */
    protected function parseLogFile(string $filePath): Collection
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return collect();
        }

        $logs = collect();
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return collect();
        }

        $currentLog = null;
        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;

            // Tenta fazer parse como JSON
            $jsonLog = $this->parseJsonLine($line);
            if ($jsonLog) {
                if ($currentLog) {
                    $logs->push($currentLog);
                }
                $currentLog = $jsonLog;
                $currentLog['line'] = $lineNumber;
                $currentLog['file'] = basename($filePath);
                continue;
            }

            // Tenta fazer parse como log Laravel padrão
            $laravelLog = $this->parseLaravelLogLine($line);
            if ($laravelLog) {
                if ($currentLog) {
                    $logs->push($currentLog);
                }
                $currentLog = $laravelLog;
                $currentLog['line'] = $lineNumber;
                $currentLog['file'] = basename($filePath);
                continue;
            }

            // Se não é início de novo log, adiciona à mensagem do log atual
            if ($currentLog) {
                $currentLog['message'] .= "\n" . trim($line);
                if (isset($currentLog['context']['full_message'])) {
                    $currentLog['context']['full_message'] .= "\n" . trim($line);
                }
            }
        }

        // Adiciona último log
        if ($currentLog) {
            $logs->push($currentLog);
        }

        fclose($handle);

        return $logs;
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

        // Normaliza estrutura
        return [
            'timestamp' => $decoded['timestamp'] ?? $decoded['datetime'] ?? now()->toIso8601String(),
            'level' => $decoded['level'] ?? $decoded['severity'] ?? 'info',
            'message' => $decoded['message'] ?? $decoded['event_name'] ?? '',
            'context' => $decoded['context'] ?? $decoded['data'] ?? $decoded,
            'format' => 'json',
        ];
    }

    /**
     * Parse de linha de log Laravel padrão
     * Formato: [2025-12-27 15:30:45] local.ERROR: Mensagem {context}
     */
    protected function parseLaravelLogLine(string $line): ?array
    {
        // Regex para log Laravel
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)/', $line, $matches)) {
            $timestamp = $matches[1];
            $environment = $matches[2];
            $level = strtolower($matches[3]);
            $messageWithContext = $matches[4];

            // Tenta extrair contexto JSON
            $context = [];
            if (preg_match('/\{.*\}$/s', $messageWithContext, $contextMatch)) {
                $contextJson = json_decode($contextMatch[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $context = $contextJson;
                    $messageWithContext = trim(str_replace($contextMatch[0], '', $messageWithContext));
                }
            }

            return [
                'timestamp' => Carbon::parse($timestamp)->toIso8601String(),
                'level' => $level,
                'message' => trim($messageWithContext),
                'context' => array_merge($context, [
                    'environment' => $environment,
                    'full_message' => $messageWithContext,
                ]),
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
        foreach ($this->logTypes as $type => $pattern) {
            $regex = '/^' . str_replace('*', '.*', $pattern) . '$/';
            if (preg_match($regex, $filename)) {
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * Formata bytes para formato legível
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
    public function getStatistics(array $filters = []): array
    {
        $logs = $this->readLogs($filters);

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
