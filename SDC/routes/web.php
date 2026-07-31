<?php



use App\Http\Controllers\Auth\EmailChangeVerificationController;
use App\Http\Controllers\ProfileController;
use App\Modules\Rat\Presentation\Http\Controllers\RatIndexController;
use App\Http\Controllers\GlobalSearchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rota local para gerar logs propositais durante desenvolvimento.
if (app()->environment(['local', 'development'])) {
    Route::get('/debug/test-log-error', function () {
    $logger = app(\App\Services\Logging\ActivityLogger::class);

    // Log de evento comum
    $logger->logEvent('system', 'test_event', ['info' => 'Isso é um teste'], 1, 'info');

    // Log de erro crítico proposital
    try {
        throw new \Exception("ERRO PROPOSITAL PARA TESTE DE INTERFACE");
    } catch (\Exception $e) {
        $logger->logCriticalError("Falha crítica detectada no teste", $e, [
            'user_id' => 1,
            'test_mode' => true
        ]);
    }

    return "Logs gerados! Verifique o Log Viewer em /log-viewer. Certifique-se de que o LOG_CHANNEL está correto (ex: stack ou daily).";
    })->name('debug.test-log-error');
}

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');





    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Email change verification (magic code flow)
    Route::post('/profile/email/verify', [EmailChangeVerificationController::class, 'verify'])
        ->name('profile.email.verify')
        ->middleware('throttle:10,1');

    Route::post('/profile/email/resend', [EmailChangeVerificationController::class, 'resend'])
        ->name('profile.email.resend')
        ->middleware('throttle:6,1');

    Route::post('/profile/email/cancel', [EmailChangeVerificationController::class, 'cancel'])
        ->name('profile.email.cancel');

    // Log Viewer - Sistema Avançado de Visualização de Logs
    Route::get('/log-viewer', function (Illuminate\Http\Request $request) {
        $logReader = app(\App\Services\Logging\LogFileReaderService::class);

        $filters = $request->only(['level', 'layer', 'search', 'date_from', 'date_to', 'errors_only', 'limit']);
        $filters['limit'] = $filters['limit'] ?? 100;

        $logs = $logReader->readLogs($filters);
        $statistics = $logReader->getStatistics($filters);

        return Inertia::render('LogViewer/Index', [
            'initialLogs' => $logs->toArray(),
            'initialStats' => $statistics,
            'availableLayers' => ['api', 'backend', 'frontend', 'system', 'security', 'database', 'queue', 'integration'],
            'availableLevels' => ['debug', 'info', 'warning', 'error', 'critical']
        ]);
    })->middleware('can:system.logs.view')->name('log-viewer.index');

    // Rota de Teste para Gerar Logs Propositais
    Route::get('/test-log-error', function (Illuminate\Http\Request $request) {
        $logger = app(\App\Services\Logging\ActivityLogger::class);
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $userId = $user?->id;

        // Log de evento comum
        $logger->logEvent('system', 'test_event', ['info' => 'Isso é um teste'], $userId, 'info');

        // Log de erro crítico proposital
        try {
            throw new \Exception("ERRO PROPOSITAL PARA TESTE DE INTERFACE");
        } catch (\Exception $e) {
            $logger->logCriticalError("Falha crítica detectada no teste", $e, [
                'user_id' => $userId,
                'test_mode' => true
            ]);
        }

        return "Logs gerados! Verifique o Log Viewer em /log-viewer.";
    })->name('test.log.error');

    // Redirect Legacy Log Viewer to New Premium Viewer
    Route::get('logs', function () {
        return redirect()->route('log-viewer.index');
    })->name('logs.index');

    // Health Check Dashboard - Visualizador de Saúde do Sistema
    Route::get('health-dashboard', function () {
        return view('health-dashboard');
    })->name('health.dashboard');

    // Global Search
    Route::get('/global-search', [GlobalSearchController::class, 'search'])
        ->middleware('throttle:30,1')
        ->name('global.search');

    // Consulta de municipios (tabela local, fonte resiliente para os selects)
    Route::get('/municipios', [\App\Http\Controllers\MunicipioController::class, 'index'])
        ->name('municipios.index');

    // Permissionamento (Admin)
    require __DIR__ . '/modules/permissions.php';

    // Inbox de notificações (sino da TopBar + histórico)
    require __DIR__ . '/modules/notificacoes.php';

    // ========================================================================
    // MÓDULOS DE NEGÓCIO
    // ========================================================================

    // Módulo: Decretações
    require __DIR__ . '/modules/decretacoes.php';

    // Módulo: Ajuda Humanitária
    require __DIR__ . '/modules/ajuda-humanitaria.php';

    // Módulo: TDAP (Gestão de Depósito)
    require __DIR__ . '/modules/tdap.php';

    // Modulo: Inventario
    require __DIR__ . '/modules/inventario.php';

    // Modulo: Estoque
    require __DIR__ . '/modules/estoque.php';

    if (file_exists(__DIR__ . '/modules/cisterna.php')) {
        require __DIR__ . '/modules/cisterna.php';
    }

    // Módulo: Compdec (Órgãos e Competências)
    require __DIR__ . '/modules/compdec.php';

    // Módulo: Demandas
    require __DIR__ . '/modules/demandas.php';

    // Módulo: PAE
    require __DIR__ . '/modules/pae.php';

    // Módulo: PMDA
    require __DIR__ . '/modules/pmda.php';

    // Módulo: Treinamento
    require __DIR__ . '/modules/treinamento.php';

    // Módulo: Suporte
    require __DIR__ . '/modules/suporte.php';

    // Modulo: IA
    require __DIR__ . '/modules/ia.php';

    // Módulo: Plantão Diário
    require __DIR__ . '/modules/plantao.php';

    // Modulos operacionais
    require __DIR__ . '/modules/rat.php';
    require __DIR__ . '/modules/plancon.php';
    require __DIR__ . '/modules/inmet.php';
});

require __DIR__ . '/auth.php';

// Health check - sem middleware para evitar dependencia de DB/session.
// Controller (nao closure) para manter as rotas cacheaveis via route:cache.
Route::get('/health', [\App\Http\Controllers\Api\HealthCheckController::class, 'ping'])->withoutMiddleware('web');
