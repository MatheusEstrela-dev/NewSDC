<?php

use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Controllers\RatUnifiedController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RAT — Estrutura Unificada: RatOcorrencia + relatos polimórficos
// ============================================================================

Route::prefix('compdec/rat')->name('compdec.rat.')->group(function () {
    // ========================================================================
    // Compdec Flow (Web / Inertia)
    // ========================================================================
    Route::get('/', [RatUnifiedController::class, 'index'])->name('index');
    Route::get('/create', [RatUnifiedController::class, 'create'])->name('create');
    Route::post('/store', [RatUnifiedController::class, 'store'])->name('store');
    Route::get('/{ocorrencia}', [RatUnifiedController::class, 'show'])->name('show');
    Route::get('/{ocorrencia}/edit', [RatUnifiedController::class, 'edit'])->name('edit');
    Route::put('/{ocorrencia}', [RatUnifiedController::class, 'update'])->name('update');
    Route::delete('/{ocorrencia}', [RatUnifiedController::class, 'destroy'])->name('destroy');
    Route::patch('/{ocorrencia}/finalize', [RatUnifiedController::class, 'finalize'])->name('finalize');
    Route::post('/{ocorrencia}/draft', [RatUnifiedController::class, 'draft'])->name('draft');

    // Boletim de Ocorrência
    Route::get('/bo', [RatUnifiedController::class, 'indexBo'])->name('bo.index');
    Route::post('/bo', [RatUnifiedController::class, 'storeBo'])->name('bo.store');

    // Auditoria
    Route::get('/audit', [RatUnifiedController::class, 'auditIndex'])->name('audit.index');
    Route::get('/audit/{id}', [RatUnifiedController::class, 'auditShow'])->name('audit.show');

    // Integrações e Dados Especializados
    Route::get('/{id}/normalized', [RatUnifiedController::class, 'normalizedData'])->name('normalized');
    Route::get('/{id}/power-bi', [RatUnifiedController::class, 'powerBiData'])->name('power-bi');
    Route::get('/{id}/json', [RatUnifiedController::class, 'showJson'])->name('show-json');
    Route::get('/export-rats', [RatUnifiedController::class, 'exportRats'])->name('export-rats');

    // ========================================================================
    // Relatos polimórficos por ocorrência (API)
    // ========================================================================
    Route::prefix('/ocorrencias/{ocorrencia}')->name('ocorrencias.')->group(function () {
        Route::get('/dados-gerais', [RatUnifiedController::class, 'showDadosGerais'])->name('dados-gerais.show');
        Route::post('/dados-gerais', [RatUnifiedController::class, 'storeDadosGerais'])->name('dados-gerais.store');
        Route::put('/dados-gerais/{id}', [RatUnifiedController::class, 'updateDadosGerais'])->name('dados-gerais.update');

        Route::get('/envolvidos', [RatUnifiedController::class, 'indexEnvolvidos'])->name('envolvidos.index');
        Route::post('/envolvidos', [RatUnifiedController::class, 'storeEnvolvidos'])->name('envolvidos.store');
        Route::put('/envolvidos/{id}', [RatUnifiedController::class, 'updateEnvolvidos'])->name('envolvidos.update');
        Route::delete('/envolvidos/{id}', [RatUnifiedController::class, 'destroyEnvolvidos'])->name('envolvidos.destroy');

        Route::get('/recursos', [RatUnifiedController::class, 'indexRecursos'])->name('recursos.index');
        Route::post('/recursos', [RatUnifiedController::class, 'storeRecursos'])->name('recursos.store');
        Route::put('/recursos/{id}', [RatUnifiedController::class, 'updateRecursos'])->name('recursos.update');
        Route::delete('/recursos/{id}', [RatUnifiedController::class, 'destroyRecursos'])->name('recursos.destroy');

        Route::get('/vistoria', [RatUnifiedController::class, 'showVistoria'])->name('vistoria.show');
        Route::post('/vistoria', [RatUnifiedController::class, 'storeVistoria'])->name('vistoria.store');
        Route::put('/vistoria/{id}', [RatUnifiedController::class, 'updateVistoria'])->name('vistoria.update');

        Route::get('/historico', [RatUnifiedController::class, 'showHistorico'])->name('historico.show');
        Route::post('/historico', [RatUnifiedController::class, 'storeHistorico'])->name('historico.store');

        Route::post('/attachments', [RatUnifiedController::class, 'storeAttachment'])->name('attachments.store');
        Route::delete('/attachments/{id}', [RatUnifiedController::class, 'destroyAttachment'])->name('attachments.destroy');
    });

    // Exportação e Estatísticas
    Route::get('/export', [RatUnifiedController::class, 'export'])->name('export');
    Route::get('/statistics', [RatUnifiedController::class, 'statistics'])->name('statistics');

    // ========================================================================
    // V1 Mobile API (Backward Compatibility)
    // ========================================================================
    Route::prefix('v1')->group(function () {
        Route::get('/ocorrencias/{id}/historico', [RatUnifiedController::class, 'v1Timeline']);
        Route::get('/ocorrencias/{id}/historico/recent', [RatUnifiedController::class, 'v1Recent']);
        Route::post('/ocorrencias', [RatUnifiedController::class, 'v1Store']);
        Route::get('/protocolos', [RatUnifiedController::class, 'protocolProxyIndex']);
    });
});


