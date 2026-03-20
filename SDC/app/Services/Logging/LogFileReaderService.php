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

            // Se não é um arquivo com data no nome, mas é o arquivo principal do tipo (ex: laravel.log, events.log)
            // adicionamos ele também se a data atual (hoje) estiver no intervalo
            if (!$isHistorical) {
                $baseName = $file['name'];
                // Remove extensões e verifica se o nome base corresponde ao tipo
                $cleanName = str_replace('.log', '', $baseName);
                
                // Se for o arquivo "vivo" (sem data no nome)
                if (!preg_match('/\d{4}-\d{2}-\d{2}/', $cleanName)) {
                    $relevantFiles[] = $file;
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

        $logs = collect();
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return collect();
        }

        $currentLog = null;
        $lineNumber = 0;
        
        // Proteção contra arquivos gigantes (ex: loop infinito ou Out Of Memory)
        $maxLines = 20000; 

        // Lê a partir do final do arquivo se possível (simulação simples com tail/fseek é complexa em PHP, usaremos limite)
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            
            if ($lineNumber > $maxLines) {
                break; // Evita estourar a memória
            }
            
            $trimmedLine = trim($line);

            // Tenta fazer parse como início de um novo log (JSON ou Laravel)
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

            // Se não é início de novo log, adiciona à mensagem do log atual
            if ($currentLog) {
                if ($trimmedLine !== '') {
                    // Limita o tamanho da mensagem para evitar OOM em stack traces gigantes
                    if (strlen($currentLog['message']) < 10000) {
                        $currentLog['message'] .= "\n" . $trimmedLine;
                    }
                }
            }
        }

        // Adiciona último log
        if ($currentLog) {
            $this->finalizeLog($currentLog);
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
