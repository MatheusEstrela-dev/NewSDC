<?php

namespace App\Services\Logging;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
     * Tamanho máximo (bytes) para incluir live files (sem data no nome) em queries genéricas.
     * Arquivos maiores são ignorados a menos que um tipo específico seja solicitado.
     */
    protected int $maxLiveFileSize = 5 * 1024 * 1024; // 5 MB

    /**
     * TTL do cache de leitura de logs (segundos)
     */
    protected int $cacheTtl = 60;

    /**
     * Tipos de log disponíveis (multiplos padroes por tipo)
     */
    protected array $logTypes = [
        'laravel'  => ['laravel.log', 'laravel-*.log'],
        'events'   => ['events.log', 'events-*.log'],
        'critical' => ['critical.log', 'critical-*.log'],
        'queries'  => ['queries.log', 'queries-*.log'],
        'jobs'     => ['jobs.log', 'jobs-*.log'],
        'docker'   => [], // virtual — sem arquivos, lido via Docker CLI/API
    ];

    public function __construct()
    {
        $this->logPath = storage_path('logs');
    }

    public function getAvailableTypes(): array
    {
        return $this->logTypes;
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
                'type' => $this->detectLogTypeByFilename(basename($file)),
            ];
        }, $files);
    }

    /**
     * Lê logs com filtros avançados (com cache)
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

        $cacheKey = 'log_reader:' . md5(serialize([
            $startDate->toIso8601String(),
            $endDate->toIso8601String(),
            $type, $level, $search, $limit, $specificFile,
        ]));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use (
            $startDate, $endDate, $type, $level, $search, $limit, $specificFile
        ) {
            $allLogs = collect();

            // Docker é tipo virtual — não lê arquivos, busca via CLI
            if ($type === 'docker') {
                $allLogs = $this->readDockerLogs($limit);
            } else {
                $logFiles = $this->getLogFilesInRange($startDate, $endDate, $type, $specificFile);
                foreach ($logFiles as $logFile) {
                    $allLogs = $allLogs->merge($this->parseLogFile($logFile['path']));
                }
            }

            return $allLogs
                ->filter(function ($log) use ($startDate, $endDate, $level, $search) {
                    if (isset($log['timestamp'])) {
                        $logDate = Carbon::parse($log['timestamp']);
                        if ($logDate->lt($startDate) || $logDate->gt($endDate)) {
                            return false;
                        }
                    }

                    if ($level && isset($log['level']) && $log['level'] !== $level) {
                        return false;
                    }

                    if ($search) {
                        if (stripos(json_encode($log), $search) === false) {
                            return false;
                        }
                    }

                    return true;
                })
                ->sortByDesc('timestamp')
                ->take($limit)
                ->values();
        });
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
                $cleanName = str_replace('.log', '', $file['name']);

                // Se for o arquivo "vivo" (sem data no nome)
                if (!preg_match('/\d{4}-\d{2}-\d{2}/', $cleanName)) {
                    // Ignora live files muito grandes em queries genéricas (sem tipo específico)
                    // para evitar OOM. Ainda acessíveis via ?type=queries ou ?type=critical
                    if ($type !== null || $file['size'] <= $this->maxLiveFileSize) {
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

        $logs = collect();
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return collect();
        }

        // Para arquivos grandes, faz seek para os últimos 2MB para obter entradas recentes
        $maxTailBytes = 2 * 1024 * 1024;
        $fileSize = filesize($filePath);
        if ($fileSize > $maxTailBytes) {
            fseek($handle, -$maxTailBytes, SEEK_END);
            fgets($handle); // descarta linha parcial no início do corte
        }

        $currentLog = null;
        $lineNumber = 0;

        // Proteção contra arquivos gigantes e lentidão severa
        $maxLines = 2000;

        // Lê com proteção de tamanho de string por linha (max ~ 512KB) para evitar alocação fatal de RAM em caso de base64/dumps gigantes sem quebra de linha.
        while (($line = fgets($handle, 512 * 1024)) !== false) {
            $lineNumber++;
            
            if ($lineNumber > $maxLines) {
                break; // Evita estourar a memória (OOM)
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

    protected array $monologLevelMap = [
        100 => 'debug', 200 => 'info', 250 => 'notice',
        300 => 'warning', 400 => 'error', 500 => 'critical',
        550 => 'alert', 600 => 'emergency',
    ];

    /**
     * Parse de linha JSON
     */
    protected function parseJsonLine(string $line): ?array
    {
        $decoded = json_decode(trim($line), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $rawLevel = $decoded['level'] ?? $decoded['severity'] ?? 'info';
        $level = is_int($rawLevel)
            ? ($this->monologLevelMap[$rawLevel] ?? strtolower($decoded['level_name'] ?? 'info'))
            : strtolower((string) $rawLevel);

        // Estrutura base normalizada
        $log = [
            'timestamp' => $decoded['timestamp'] ?? $decoded['datetime'] ?? now()->toIso8601String(),
            'level'     => $level,
            'message'   => $decoded['message'] ?? $decoded['event_name'] ?? '',
            'context'   => $decoded['context'] ?? $decoded['data'] ?? $decoded,
            'extra'     => $decoded['extra'] ?? [],
            'channel'   => $decoded['channel'] ?? null,
            'format'    => 'json',
        ];

        // Aplica flatten para mover campos aninhados para o root
        return $this->flattenLogStructure($log);
    }

    /**
     * Detecta a camada arquitetural baseado no namespace/path
     */
    protected function detectArchitecturalLayer(?string $className, ?string $filePath): string
    {
        $patterns = [
            'Controller' => ['Controller', 'Controllers'],
            'Service'    => ['Service', 'Services'],
            'Repository' => ['Repository', 'Repositories'],
            'Model'      => ['Model', 'Models'],
            'Middleware' => ['Middleware'],
            'Request'    => ['Request', 'Requests'],
            'Job'        => ['Job', 'Jobs'],
            'Event'      => ['Event', 'Events'],
            'Listener'   => ['Listener', 'Listeners'],
            'Command'    => ['Command', 'Commands'],
            'DTO'        => ['DTO', 'DataTransferObject'],
            'Resource'   => ['Resource', 'Resources'],
            'Policy'     => ['Policy', 'Policies'],
            'Observer'   => ['Observer', 'Observers'],
            'Trait'      => ['Trait', 'Traits'],
        ];

        $searchIn = ($className ?? '') . '|' . ($filePath ?? '');

        foreach ($patterns as $layer => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($searchIn, $keyword) !== false) {
                    return $layer;
                }
            }
        }

        return 'System';
    }

    /**
     * Faz flatten da estrutura aninhada do log para campos no root
     * Resolve o problema de source estar dentro de context
     */
    protected function flattenLogStructure(array $log): array
    {
        // Extrai source de dentro do context (ActivityLogger salva assim)
        $source = $log['context']['source'] ?? $log['source'] ?? [];

        // Extrai dados do ActivityLogger
        $eventType = $log['context']['event_type'] ?? $log['event_type'] ?? null;
        $requestId = $log['context']['request_id'] ?? $log['request_id'] ?? null;
        $userId = $log['context']['user_id'] ?? $log['user_id'] ?? null;
        $ipAddress = $log['context']['ip_address'] ?? $log['ip_address'] ?? null;
        $url = $log['context']['url'] ?? $log['url'] ?? null;
        $httpMethod = $log['context']['http_method'] ?? $log['http_method'] ?? null;
        $userAgent = $log['context']['user_agent'] ?? $log['user_agent'] ?? null;
        $hostname = $log['context']['hostname'] ?? $log['hostname'] ?? null;

        // Extrai dados de erro critico
        $errorFile = $log['context']['data']['error_file'] ?? $log['context']['error_file'] ?? null;
        $errorLine = $log['context']['data']['error_line'] ?? $log['context']['error_line'] ?? null;
        $stackTrace = $log['context']['data']['full_trace'] ?? $log['context']['full_trace'] ?? null;
        $exceptionClass = $log['context']['data']['exception_class'] ?? $log['context']['exception_class'] ?? null;

        // Campos de origem - prioriza source, depois dados de erro
        $className = $source['class'] ?? $exceptionClass ?? null;
        $methodName = $source['function'] ?? $source['method'] ?? null;
        $filePath = $source['file_path'] ?? $source['file'] ?? $errorFile ?? null;
        $lineNumber = $source['line'] ?? $errorLine ?? null;

        // Campos SQL (queries.log)
        $sql = $log['context']['sql'] ?? null;
        $bindings = $log['context']['bindings'] ?? null;
        $timeMsRaw = $log['context']['time_ms'] ?? $log['context']['time'] ?? null;
        $timeMs = $timeMsRaw !== null ? (float) $timeMsRaw : null;
        $thresholdMs = $log['context']['threshold_ms'] ?? null;
        $sqlSeverity = $log['context']['severity'] ?? null;
        $connection = $log['context']['connection'] ?? null;
        $queryType = $sql ? $this->detectQueryType($sql) : null;

        // Source tracking dos queries (salvo diretamente no context)
        if (!$className && isset($log['context']['class'])) {
            $className = $log['context']['class'];
        }
        if (!$methodName && isset($log['context']['method'])) {
            $methodName = $log['context']['method'];
        }
        if (!$filePath && isset($log['context']['file_path'])) {
            $filePath = $log['context']['file_path'];
        }
        if (!$lineNumber && isset($log['context']['line'])) {
            $lineNumber = $log['context']['line'];
        }

        // Extrai layer do context ou do extra (IntrospectionProcessor)
        $extra = $log['extra'] ?? [];
        if (!$className && isset($extra['class']) && !str_contains((string)($extra['class'] ?? ''), 'Illuminate\\Log')) {
            $className = $extra['class'];
            $methodName = $methodName ?? $extra['function'] ?? null;
            $lineNumber = $lineNumber ?? $extra['line'] ?? null;
        }

        // Resolve o file_path completo para debug rapido
        $fullFilePath = $this->resolveFullFilePath($filePath, $className);

        // Detecta layer baseado no namespace
        $layer = $source['layer'] ?? $log['context']['layer'] ?? $this->detectArchitecturalLayer($className, $fullFilePath);

        // Discriminador de tipo de log
        $logType = $this->detectLogType($log['channel'] ?? null, $sql, $log['context'] ?? []);

        return array_merge($log, [
            // Campos de origem (FLAT no root)
            'class'    => $className,
            'method'   => $methodName,
            'file'     => $filePath ? basename($filePath) : null,
            'file_path' => $fullFilePath,
            'line'     => $lineNumber,
            'layer'    => $layer,

            // Campos de contexto HTTP
            'url'         => $url,
            'http_method' => $httpMethod,
            'remote_addr' => $ipAddress,
            'user_agent'  => $userAgent,

            // Campos de rastreamento
            'request_id' => $requestId,
            'user_id'    => $userId,
            'hostname'   => $hostname,
            'event_type' => $eventType,

            // Stack trace para erros
            'stack_trace' => $stackTrace,

            // Campos SQL
            'sql'          => $sql,
            'bindings'     => $bindings,
            'time_ms'      => $timeMs,
            'threshold_ms' => $thresholdMs,
            'sql_severity' => $sqlSeverity,
            'connection'   => $connection,
            'query_type'   => $queryType,

            // Tipo de log (discriminador)
            'log_type' => $logType,

            // Campos de critical
            'system_metrics'     => $log['context']['system_metrics'] ?? null,
            'stack_frames'       => is_array($log['context']['stack_trace'] ?? null) ? $log['context']['stack_trace'] : null,
            'exception_class'    => $exceptionClass,
            'previous_exception' => $log['context']['previous_exception'] ?? null,
        ]);
    }

    protected function detectQueryType(?string $sql): ?string
    {
        if (!$sql) {
            return null;
        }
        $first = strtoupper(trim(preg_replace('/\s+/', ' ', substr($sql, 0, 20))));
        foreach (['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'TRUNCATE'] as $type) {
            if (str_starts_with($first, $type)) {
                return $type;
            }
        }
        return 'OTHER';
    }

    protected function detectLogType(?string $channel, ?string $sql, array $context): string
    {
        if ($sql || $channel === 'queries') {
            return 'query';
        }
        if ($channel === 'critical' || isset($context['exception_class'])) {
            return 'critical';
        }
        if ($channel === 'events' || isset($context['event_type'])) {
            return 'event';
        }
        return 'laravel';
    }

    public function detectLayerPublic(?string $className, ?string $filePath): string
    {
        return $this->detectArchitecturalLayer($className, $filePath);
    }

    // -------------------------------------------------------------------------
    // Docker CLI log reading
    // -------------------------------------------------------------------------

    protected function isDockerCliAvailable(): bool
    {
        exec('docker info 2>&1', $out, $code);
        return $code === 0;
    }

    protected function readDockerLogs(int $limit = 200): Collection
    {
        if (!$this->isDockerCliAvailable()) {
            return collect();
        }

        $tail = min(500, $limit * 3);

        exec('docker ps --format "{{.Names}}" 2>&1', $containerNames, $code);
        if ($code !== 0 || empty($containerNames)) {
            return collect();
        }

        $allLogs = collect();

        foreach ($containerNames as $containerName) {
            $containerName = trim($containerName);
            if ($containerName === '') {
                continue;
            }

            $command = sprintf(
                'docker logs --tail %d --timestamps %s 2>&1',
                $tail,
                escapeshellarg($containerName)
            );

            exec($command, $output, $returnCode);

            foreach ($output as $line) {
                $parsed = $this->parseDockerLogLine($line, $containerName);
                if ($parsed) {
                    $allLogs->push($parsed);
                }
            }

            $output = [];
        }

        return $allLogs->sortByDesc('timestamp')->values();
    }

    protected function parseDockerLogLine(string $line, string $containerName = ''): ?array
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }

        // Docker --timestamps format: "2026-03-20T10:23:45.123456789Z message..."
        $timestamp = null;
        $message   = $line;

        if (preg_match('/^(\d{4}-\d{2}-\d{2}T[\d:.]+Z)\s+(.+)$/s', $line, $m)) {
            $timestamp = $m[1];
            $message   = $m[2];
        }

        // Tenta parse como JSON (canal json_stderr / docker do Laravel)
        $trimmed = ltrim($message);
        if (str_starts_with($trimmed, '{')) {
            $json = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $this->normalizeDockerJsonLog($json, $timestamp, $containerName);
            }
        }

        // Plain text (nginx, redis, mysql, etc.)
        $level = $this->detectLevelFromText($message);

        return $this->buildDockerLogEntry($timestamp, $level, $message, $containerName, 'text');
    }

    protected function normalizeDockerJsonLog(array $json, ?string $dockerTs, string $containerName): array
    {
        $rawLevel = $json['level'] ?? $json['severity'] ?? 'info';
        $level    = is_int($rawLevel)
            ? ($this->monologLevelMap[$rawLevel] ?? strtolower($json['level_name'] ?? 'info'))
            : strtolower((string) $rawLevel);

        $extra = $json['extra'] ?? [];
        $ctx   = $json['context'] ?? [];

        $entry = $this->buildDockerLogEntry(
            $dockerTs ?? $json['datetime'] ?? now()->toIso8601String(),
            $level,
            $json['message'] ?? '',
            $containerName,
            'json'
        );

        // Enriquece com campos do Monolog
        $entry['class']      = $extra['class'] ?? null;
        $entry['method']     = $extra['function'] ?? null;
        $entry['line']       = $extra['line'] ?? null;
        $entry['request_id'] = $ctx['request_id'] ?? $extra['request_id'] ?? null;
        $entry['user_id']    = $ctx['user_id'] ?? null;
        $entry['url']        = $ctx['url'] ?? null;
        $entry['http_method']= $ctx['http_method'] ?? null;

        // Detecta layer pelo namespace
        if ($entry['class']) {
            $entry['layer'] = $this->detectArchitecturalLayer($entry['class'], null);
        }

        return $entry;
    }

    protected function buildDockerLogEntry(
        ?string $timestamp,
        string  $level,
        string  $message,
        string  $containerName,
        string  $format
    ): array {
        return [
            'timestamp'    => $timestamp ?? now()->toIso8601String(),
            'level'        => $level,
            'message'      => $message,
            'log_type'     => 'docker',
            'layer'        => $containerName,
            'class'        => null,
            'method'       => null,
            'file'         => null,
            'file_path'    => null,
            'line'         => null,
            'url'          => null,
            'http_method'  => null,
            'remote_addr'  => null,
            'user_agent'   => null,
            'request_id'   => null,
            'user_id'      => null,
            'hostname'     => $containerName,
            'event_type'   => null,
            'stack_trace'  => null,
            'sql'          => null,
            'bindings'     => null,
            'time_ms'      => null,
            'threshold_ms' => null,
            'sql_severity' => null,
            'connection'   => null,
            'query_type'   => null,
            'system_metrics'     => null,
            'stack_frames'       => null,
            'exception_class'    => null,
            'previous_exception' => null,
            'format'       => $format,
        ];
    }

    protected function detectLevelFromText(string $message): string
    {
        $lower = strtolower($message);
        if (preg_match('/\b(emergency|emerg)\b/', $lower))      return 'emergency';
        if (preg_match('/\b(alert)\b/', $lower))                return 'alert';
        if (preg_match('/\b(critical|crit|fatal)\b/', $lower))  return 'critical';
        if (preg_match('/\b(error|err)\b/', $lower))            return 'error';
        if (preg_match('/\b(warn(?:ing)?)\b/', $lower))         return 'warning';
        if (preg_match('/\b(notice)\b/', $lower))               return 'notice';
        if (preg_match('/\b(debug|dbg)\b/', $lower))            return 'debug';
        return 'info';
    }

    /**
     * Resolve o caminho completo do arquivo baseado no namespace
     */
    protected function resolveFullFilePath(?string $filename, ?string $className): ?string
    {
        if (!$filename && !$className) {
            return null;
        }

        // Se ja e um path completo valido
        if ($filename && (str_contains($filename, '/') || str_contains($filename, '\\'))) {
            return $filename;
        }

        if ($className) {
            // Converte namespace para path: App\Http\Controllers\UserController -> app/Http/Controllers/UserController.php
            $relativePath = str_replace('\\', '/', $className) . '.php';
            $relativePath = preg_replace('/^App\//', 'app/', $relativePath);

            if (file_exists(base_path($relativePath))) {
                return $relativePath;
            }
        }

        return $filename;
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

        // Aplica flatten para garantir campos no root (ambos formatos)
        $flattened = $this->flattenLogStructure($log);
        foreach ($flattened as $key => $value) {
            $log[$key] = $value;
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
    protected function detectLogTypeByFilename(string $filename): string
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
     * Obtém estatísticas dos logs.
     * Aceita Collection pre-carregada para evitar double read.
     */
    public function getStatistics(array $filters = [], ?Collection $preloadedLogs = null): array
    {
        $logs = $preloadedLogs ?? $this->readLogs($filters);

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
