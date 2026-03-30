<?php

use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Controllers\RatUnifiedController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RAT — Estrutura Unificada: RatOcorrencia + relatos polimórficos
// ============================================================================

Route::prefix('compdec/rat')->name('compdec.rat.')->group(function () {

    // Boletim de Ocorrência
    Route::get('/bo', [RatUnifiedController::class, 'indexBo'])
        ->name('bo.index')
        ->middleware('can:rat.protocolos.view');

    Route::post('/bo', [RatUnifiedController::class, 'storeBo'])
        ->name('bo.store')
        ->middleware('can:rat.protocolos.create');

    // Ocorrências - Listagem e Show
    Route::get('/ocorrencias/{id}', [RatUnifiedController::class, 'showBo'])
        ->name('ocorrencias.show')
        ->middleware('can:rat.protocolos.view');

    // ========================================================================
    // Relatos polimórficos por ocorrência
    // ========================================================================
    Route::prefix('/ocorrencias/{ocorrencia}')->name('ocorrencias.')->group(function () {

        // Dados Gerais
        Route::get('/dados-gerais', [RatUnifiedController::class, 'showDadosGerais'])
            ->name('dados-gerais.show')
            ->middleware('can:rat.protocolos.view');

        Route::post('/dados-gerais', [RatUnifiedController::class, 'storeDadosGerais'])
            ->name('dados-gerais.store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/dados-gerais/{id}', [RatUnifiedController::class, 'updateDadosGerais'])
            ->name('dados-gerais.update')
            ->middleware('can:rat.relatos.manage');

        // Envolvidos
        Route::get('/envolvidos', [RatUnifiedController::class, 'indexEnvolvidos'])
            ->name('envolvidos.index')
            ->middleware('can:rat.protocolos.view');

        Route::post('/envolvidos', [RatUnifiedController::class, 'storeEnvolvidos'])
            ->name('envolvidos.store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/envolvidos/{id}', [RatUnifiedController::class, 'updateEnvolvidos'])
            ->name('envolvidos.update')
            ->middleware('can:rat.relatos.manage');

        Route::delete('/envolvidos/{id}', [RatUnifiedController::class, 'destroyEnvolvidos'])
            ->name('envolvidos.destroy')
            ->middleware('can:rat.relatos.manage');

        // Recursos empregados
        Route::get('/recursos', [RatUnifiedController::class, 'indexRecursos'])
            ->name('recursos.index')
            ->middleware('can:rat.protocolos.view');

        Route::post('/recursos', [RatUnifiedController::class, 'storeRecursos'])
            ->name('recursos.store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/recursos/{id}', [RatUnifiedController::class, 'updateRecursos'])
            ->name('recursos.update')
            ->middleware('can:rat.relatos.manage');

        Route::delete('/recursos/{id}', [RatUnifiedController::class, 'destroyRecursos'])
            ->name('recursos.destroy')
            ->middleware('can:rat.relatos.manage');

        // Vistoria técnica
        Route::get('/vistoria', [RatUnifiedController::class, 'showVistoria'])
            ->name('vistoria.show')
            ->middleware('can:rat.protocolos.view');

        Route::post('/vistoria', [RatUnifiedController::class, 'storeVistoria'])
            ->name('vistoria.store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/vistoria/{id}', [RatUnifiedController::class, 'updateVistoria'])
            ->name('vistoria.update')
            ->middleware('can:rat.relatos.manage');

        // Histórico
        Route::get('/historico', [RatUnifiedController::class, 'showHistorico'])
            ->name('historico.show')
            ->middleware('can:rat.protocolos.view');

        Route::post('/historico', [RatUnifiedController::class, 'storeHistorico'])
            ->name('historico.store')
            ->middleware('can:rat.relatos.manage');

        // Anexos
        Route::post('/attachments', [RatUnifiedController::class, 'storeAttachment'])
            ->name('attachments.store')
            ->middleware('can:rat.relatos.manage');

        Route::delete('/attachments/{id}', [RatUnifiedController::class, 'destroyAttachment'])
            ->name('attachments.destroy')
            ->middleware('can:rat.relatos.manage');
    });

    // Exportação e Estatísticas
    Route::get('/export', [RatUnifiedController::class, 'export'])
        ->name('export')
        ->middleware('can:rat.protocolos.export');

    Route::get('/statistics', [RatUnifiedController::class, 'statistics'])
        ->name('statistics')
        ->middleware('can:rat.protocolos.view');
});

