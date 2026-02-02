<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Modules\Rat\Presentation\Http\Controllers\RatIndexController;
use App\Modules\Rat\Presentation\Http\Controllers\RatSyncController;

Route::prefix('rat')->name('rat.')->group(function () {
    // Rota de Sincronização Offline (Deve vir antes de /{id})
    Route::post('/sync', [RatSyncController::class, 'sync'])->name('sync');

    // Rota para exportar RATs como CSV (DEVE vir antes de rotas com parâmetros como {id})
    Route::get('/export', [RatIndexController::class, 'export'])->name('export');

    // Listagem de RATs
    Route::get('/', [RatIndexController::class, 'index'])->name('index');

    // Criar novo RAT
    Route::get('/create', function () {
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
    })->name('create');

    // Rota para retornar dados JSON de um RAT específico
    Route::get('/{id}/json', [RatIndexController::class, 'showJson'])->name('show.json');

    // Visualizar/Editar RAT existente
    Route::get('/{id}', function ($id) {
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
    })->name('show');
});

