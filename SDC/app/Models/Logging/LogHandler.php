<?php

namespace App\Models\Logging;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class LogHandler extends AbstractProcessingHandler
{
    protected $table;

    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        $this->table = 'logs';
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        try {
            $context = $record['context'] ?? [];

            $data = [
                'level'         => strtolower($record['level_name'] ?? 'error'),
                'message'       => $record['message'] ?? '',
                'instance'      => substr(gethostname(), 0, 40),
                'user_id'       => $context['user_id'] ?? (Auth::check() ? Auth::user()->id : null),
                'channel'       => substr($record['channel'] ?? '', 0, 15),
                'formatted'     => $record['formatted'] ?? '',
                'remote_addr'   => substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 30),
                'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'cli',
                'class'         => isset($context['class']) ? substr($context['class'], 0, 255) : null,
                'method'        => isset($context['method']) ? substr($context['method'], 0, 100) : null,
                'line'          => $context['line'] ?? null,
                'file'          => isset($context['file']) ? substr($context['file'], 0, 500) : null,
                'url'           => substr($context['url'] ?? ($_SERVER['REQUEST_URI'] ?? ''), 0, 500),
                'http_method'   => substr($context['http_method'] ?? ($_SERVER['REQUEST_METHOD'] ?? ''), 0, 10),
                'request_data'  => $context['request_data'] ?? null,
                'stack_trace'   => $context['stack_trace'] ?? null,
                'layer'         => isset($context['layer']) ? substr($context['layer'], 0, 50) : null,
                'request_id'    => $context['request_id'] ?? null,
                'duration_ms'   => $context['duration_ms'] ?? null,
                'memory_usage'  => $context['memory_usage'] ?? null,
                'event'         => isset($context['event']) ? substr($context['event'], 0, 50) : null,
                'model_id'      => isset($context['model_id']) ? substr((string) $context['model_id'], 0, 100) : null,
                'model_table'   => isset($context['table']) ? substr($context['table'], 0, 100) : null,
                'tags'          => isset($context['tags']) ? json_encode($context['tags']) : null,
                'context'       => json_encode($this->filterContext($context)),
            ];

            DB::connection()->table($this->table)->insert($data);
        } catch (\Exception $e) {
            error_log('[LogHandler] Falha ao salvar log no BD: ' . $e->getMessage());
        }
    }

    protected function filterContext(array $context): array
    {
        $exclude = [
            'class', 'method', 'line', 'file', 'url', 'http_method',
            'request_data', 'stack_trace', 'layer', 'user_id',
            'request_id', 'duration_ms', 'memory_usage', 'event',
            'model_id', 'table', 'tags',
        ];
        return array_diff_key($context, array_flip($exclude));
    }
}
