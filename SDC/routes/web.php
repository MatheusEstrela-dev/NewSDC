<?php

use App\Http\Controllers\ProfileController;
use App\Modules\Rat\Presentation\Http\Controllers\RatIndexController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Modules\Tdap\Application\UseCases\ListMovimentacoesUseCase;

// DEBUG: Rota temporária para testar serialização
Route::get('/debug/movimentacoes', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Debug route is working',
        'opcache_enabled' => function_exists('opcache_reset'),
        'php_version' => PHP_VERSION,
        'timestamp' => now()->toIso8601String(),
    ]);
});

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

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // DEMANDAS - Listagem (placeholder)
    Route::get('/demandas', function () {
        return Inertia::render('DemandasIndex');
    })->name('demandas.index');

    // PAE - Protocolos (listagem)
    Route::get('/pae/protocolo', function () {
        return Inertia::render('PaeProtocolosIndex');
    })->name('pae.protocolos.index');

    Route::get('/pae', function () {
        return Inertia::render('Pae');
    })->name('pae.index');

    // Listagem de RATs
    Route::get('/rat', [RatIndexController::class, 'index'])->name('rat.index');

    // Criar novo RAT
    Route::get('/rat/create', function () {
        return Inertia::render('Rat', [
            'rat' => [
                'id' => null,
                'protocolo' => '',
                'status' => 'rascunho',
                'tem_vistoria' => false,
                'dadosGerais' => [
                    'data_fato' => '',
                    'data_inicio_atividade' => '',
                    'data_termino_atividade' => '',
                    'nat_cobrade_id' => '',
                    'nat_nome_operacao' => '',
                    'local_municipio' => '',
                ],
            ],
            'recursos' => [],
            'envolvidos' => [],
            'vistoria' => [],
            'historyEvents' => [],
            'anexos' => [],
            'lastUpdate' => now()->format('d/m/Y H:i'),
        ]);
    })->name('rat.create');

    // Visualizar/Editar RAT existente
    Route::get('/rat/{id}', function ($id) {
        // TODO: Buscar RAT do banco de dados pelo ID
        return Inertia::render('Rat', [
            'rat' => [
                'id' => $id,
                'protocolo' => 'RAT-2024-001',
                'status' => 'em_andamento',
                'tem_vistoria' => false,
                'dadosGerais' => [
                    'data_fato' => now()->subDays(3)->format('Y-m-d\\TH:i'),
                    'data_inicio_atividade' => now()->subDays(3)->format('Y-m-d\\TH:i'),
                    'data_termino_atividade' => '',
                    'nat_cobrade_id' => '',
                    'nat_nome_operacao' => '',
                    'local_municipio' => '',
                ],
            ],
            'recursos' => [],
            'envolvidos' => [],
            'vistoria' => [],
            'historyEvents' => [],
            'anexos' => [],
            'lastUpdate' => now()->format('d/m/Y H:i'),
        ]);
    })->name('rat.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Log Viewer - Sistema Avançado de Visualização de Logs
    Route::get('/log-viewer', function () {
        return Inertia::render('LogViewer/Index');
    })->middleware('can:logs.view')->name('log-viewer.index');

    // Redirect Legacy Log Viewer to New Premium Viewer
    Route::get('logs', function () {
        return redirect()->route('log-viewer.index');
    })->name('logs.index');

    // Health Check Dashboard - Visualizador de Saúde do Sistema
    Route::get('health-dashboard', function () {
        return view('health-dashboard');
    })->name('health.dashboard');

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

    // Módulo: Treinamento
    require __DIR__ . '/modules/treinamento.php';

    // Módulo: RAT (Registro de Atendimento Técnico)
    require __DIR__ . '/modules/rat.php';
});

require __DIR__ . '/auth.php';
