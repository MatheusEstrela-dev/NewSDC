<?php

namespace App\Http\Controllers;

use App\Services\Logging\LogFileReaderService;
use App\Services\Logging\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

class LogViewerController extends Controller
{
    protected LogFileReaderService $logReader;

    public function __construct(LogFileReaderService $logReader)
    {
        $this->logReader = $logReader;
    }

    /**
     * Dashboard Principal do Log Viewer
     */
    public function index(Request $request)
    {
        $filters = $request->only(['level', 'layer', 'search', 'date_from', 'date_to', 'errors_only', 'limit', 'type']);
        $filters['limit'] = $filters['limit'] ?? 100;

        // Converte date_from/date_to para start_date/end_date
        if (isset($filters['date_from'])) {
            $filters['start_date'] = $filters['date_from'];
        }
        if (isset($filters['date_to'])) {
            $filters['end_date'] = $filters['date_to'];
        }

        $logs = $this->logReader->readLogs($filters);

        // Calcula estatisticas reutilizando os logs ja carregados (evita double-read)
        $statistics = $this->logReader->getStatistics($filters, $logs);

        // Filtra por layer se especificado no controller (extra filter)
        $layerFilter = $filters['layer'] ?? null;
        if ($layerFilter) {
            $logs = $logs->filter(fn($log) => ($log['layer'] ?? '') === $layerFilter);
        }

        // Filtra apenas erros se solicitado
        if (!empty($filters['errors_only'])) {
            $errorLevels = ['error', 'critical', 'alert', 'emergency'];
            $logs = $logs->filter(fn($log) => in_array(strtolower($log['level'] ?? ''), $errorLevels));
        }

        // Busca layers dinamicos dos logs carregados
        $detectedLayers = $logs->pluck('layer')->filter()->unique()->sort()->values()->toArray();

        // Layers estaticos padrao
        $staticLayers = ['Controller', 'Service', 'Repository', 'Model', 'Middleware', 'Request', 'Job', 'Command', 'System'];
        $availableLayers = array_values(array_unique(array_merge($staticLayers, $detectedLayers)));
        sort($availableLayers);

        // Stripe campos pesados do payload Inertia para evitar OOM no browser.
        // context e extra sao duplicatas dos campos ja extraidos no flat (sql, class, method etc).
        // stack_trace e truncado pois pode ter varios KB por entrada.
        $listLogs = $logs->map(function (array $log): array {
            unset($log['context'], $log['extra']);
            if (isset($log['stack_trace']) && strlen((string) $log['stack_trace']) > 3000) {
                $log['stack_trace'] = substr($log['stack_trace'], 0, 3000) . "\n[truncated]";
            }
            return $log;
        })->values()->toArray();

        return Inertia::render('LogViewer/Index', [
            'initialLogs'    => $listLogs,
            'initialStats'   => $statistics,
            'availableLayers' => $availableLayers,
            'availableLevels' => ['debug', 'info', 'warning', 'error', 'critical'],
            'availableTypes'  => array_keys($this->logReader->getAvailableTypes())
        ]);
    }

    /**
     * Limpa um arquivo de log específico
     */
    public function clear(Request $request)
    {
        $filename = basename($request->input('file', 'laravel.log'));

        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return back()->with('error', 'Nome de arquivo invalido.');
        }

        $path = storage_path('logs/' . $filename);
        
        if (File::exists($path)) {
            File::put($path, '');
            ActivityLogger::logEvent('system', 'log_cleared', ['file' => $filename], auth()->id(), 'warning');
            return back()->with('success', "Log $filename limpo com sucesso.");
        }

        return back()->with('error', "Arquivo de log não encontrado.");
    }

    /**
     * Executa limpeza de logs antigos (rotatividade manual)
     */
    public function cleanOldLogs(Request $request)
    {
        $days = $request->input('days', 30);
        $deletedCount = $this->logReader->cleanOldLogs($days);
        
        ActivityLogger::logEvent('system', 'logs_rotated', ['deleted_count' => $deletedCount, 'days' => $days], auth()->id(), 'info');
        
        return back()->with('success', "$deletedCount arquivos de log antigos foram removidos.");
    }

    /**
     * Download de um log completo
     */
    public function download(Request $request)
    {
        $filename = basename($request->input('file', 'laravel.log'));

        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return abort(400, 'Nome de arquivo invalido.');
        }

        $path = storage_path('logs/' . $filename);

        if (File::exists($path)) {
            ActivityLogger::logEvent('system', 'log_downloaded', ['file' => $filename], auth()->id(), 'info');
            return response()->download($path);
        }

        return abort(404);
    }

    /**
     * Gera logs de teste
     */
    public function generateTestLogs()
    {
        $logger = app(ActivityLogger::class);
        
        $logger->logEvent('system', 'ui_test_event', ['info' => 'Evento de teste gerado pela engranagem'], auth()->id(), 'info');
        
        try {
            throw new \Exception("ERRO DE TESTE: Verificando interface do Log Viewer");
        } catch (\Exception $e) {
            $logger->logCriticalError("Simulação de falha crítica", $e, ['test' => true]);
        }

        return back()->with('success', 'Logs de teste gerados com sucesso.');
    }
}
