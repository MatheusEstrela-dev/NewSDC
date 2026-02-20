<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('rat')->name('rat.')->group(function () {

    Route::get('/', function () {
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
            'lastUpdate' => now()->format('d/m/Y H:i'),
        ]);
    })->name('index');

    // Rota para retornar dados JSON de um RAT especifico
    Route::get('/{id}/json', [RatIndexController::class, 'showJson'])
        ->name('show.json')
        ->middleware('can:rat.protocolos.view');

    // Editar RAT existente
    Route::get('/{id}/edit', function ($id) {
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
            'mode' => 'edit',
        ]);
    })->name('edit')
      ->middleware('can:rat.protocolos.edit');

    // TODO: Atualizar RAT (implementar quando existir UpdateRatUseCase)
    // Route::put('/{id}', [RatIndexController::class, 'update'])
    //     ->name('update')
    //     ->middleware('can:rat.protocolos.edit');

    // Finalizar RAT
    Route::patch('/{id}/finalize', [RatIndexController::class, 'finalize'])
        ->name('finalize')
        ->middleware('can:rat.protocolos.finalize');

    // Visualizar RAT existente (somente leitura)
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
            'mode' => 'view',
        ]);
    })->name('show')
      ->middleware('can:rat.protocolos.view');

    // Deletar RAT
    Route::delete('/{id}', [RatIndexController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:rat.protocolos.delete');
});
