<?php



use App\Http\Controllers\ProfileController;
use App\Modules\Rat\Presentation\Http\Controllers\RatIndexController;
use App\Http\Controllers\GlobalSearchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Modules\Tdap\Application\UseCases\ListMovimentacoesUseCase;

// DEBUG: Rota para testar executeAsDTO (requer container)
Route::get('/debug/test-dto', function () {
    try {
        $useCase = app(ListMovimentacoesUseCase::class);
        $result = $useCase->executeAsDTO([], 15);

        return response()->json([
            'debug' => 'Testing executeAsDTO method',
            'result_type' => gettype($result),
            'is_array' => is_array($result),
            'keys' => array_keys($result),
            'data_type' => gettype($result['data'] ?? null),
            'data_count' => is_array($result['data'] ?? null) ? count($result['data']) : 'N/A',
            'pagination' => $result['pagination'] ?? null,
            'first_item' => $result['data'][0] ?? null,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');





    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Log Viewer - Sistema Avançado de Visualização de Logs
    Route::get('/log-viewer', function () {
        return Inertia::render('LogViewer/Index');
    })->middleware('can:system.logs.view')->name('log-viewer.index');

    // Redirect Legacy Log Viewer to New Premium Viewer
    Route::get('logs', function () {
        return redirect()->route('log-viewer.index');
    })->name('logs.index');

    // Health Check Dashboard - Visualizador de Saúde do Sistema
    Route::get('health-dashboard', function () {
        return view('health-dashboard');
    })->name('health.dashboard');

    // Global Search
    Route::get('/global-search', [GlobalSearchController::class, 'index'])->name('global.search');

    // Permissionamento (Admin)
    require __DIR__ . '/modules/permissions.php';

    // ========================================================================
    // MÓDULOS DE NEGÓCIO
    // ========================================================================

    // Módulo: Decretações
    require __DIR__ . '/modules/decretacoes.php';

    // Módulo: Ajuda Humanitária
    require __DIR__ . '/modules/ajuda-humanitaria.php';

    // Módulo: TDAP (Gestão de Depósito)
    require __DIR__ . '/modules/tdap.php';

    // Módulo: Compdec (Órgãos e Competências)
    require __DIR__ . '/modules/compdec.php';

    // Módulo: Demandas
    require __DIR__ . '/modules/demandas.php';

    // Módulo: PAE
    require __DIR__ . '/modules/pae.php';

    // Módulo: Treinamento
    require __DIR__ . '/modules/treinamento.php';

    // Módulo: RAT (Registro de Atendimento Técnico)
    require __DIR__ . '/modules/rat.php';

    // Módulo: Suporte
    require __DIR__ . '/modules/suporte.php';

    // Modulo: IA
    require __DIR__ . '/modules/ia.php';

    // Modulo: Inmet (Meteorologia)
    require __DIR__ . '/modules/inmet.php';

    // Módulo: Plantão Diário
    require __DIR__ . '/modules/plantao.php';

    // Módulo: PlanCon (Plano de Contingência)
    require __DIR__ . '/modules/plancon.php';
});

require __DIR__ . '/auth.php';
