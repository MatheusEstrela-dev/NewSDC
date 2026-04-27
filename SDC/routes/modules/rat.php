<?php

use App\Modules\Rat\Controllers\RatUnifiedController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RAT — Rotas unificadas sob /rat (nome: compdec.rat.* para compatibilidade)
// Padrão: Request → Controller → Service → Model → Resource
// ============================================================================

Route::prefix('rat')->name('compdec.rat.')->group(function () {

    // ========================================================================
    // Rotas Web / Inertia (CRUD principal)
    // ========================================================================
    Route::get('/',                          [RatUnifiedController::class, 'index'])->name('index');
    Route::get('/create',                    [RatUnifiedController::class, 'create'])->name('create');
    Route::post('/',                         [RatUnifiedController::class, 'store'])->name('store');
    Route::get('/{ocorrencia}',              [RatUnifiedController::class, 'show'])->name('show');
    Route::get('/{ocorrencia}/edit',         [RatUnifiedController::class, 'edit'])->name('edit');
    Route::put('/{ocorrencia}',              [RatUnifiedController::class, 'update'])->name('update');
    Route::delete('/{ocorrencia}',           [RatUnifiedController::class, 'destroy'])->name('destroy');
    Route::patch('/{ocorrencia}/finalize',   [RatUnifiedController::class, 'finalize'])->name('finalize');
    Route::match(['post', 'patch'], '/{ocorrencia}/draft', [RatUnifiedController::class, 'draft'])->name('draft');

    // ========================================================================
    // Exportação e Estatísticas (sem parâmetro de rota — antes do wildcard)
    // ========================================================================
    Route::get('/export',       [RatUnifiedController::class, 'export'])->name('export');
    Route::get('/export-rats',  [RatUnifiedController::class, 'exportRats'])->name('export-rats');
    Route::get('/statistics',   [RatUnifiedController::class, 'statistics'])->name('statistics');

    // ========================================================================
    // Boletim de Ocorrência
    // ========================================================================
    Route::prefix('bo')->name('bo.')->group(function () {
        Route::get('/',  [RatUnifiedController::class, 'indexBo'])->name('index');
        Route::post('/', [RatUnifiedController::class, 'storeBo'])->name('store');
    });

    // ========================================================================
    // Auditoria
    // ========================================================================
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/',      [RatUnifiedController::class, 'auditIndex'])->name('index');
        Route::get('/{id}',  [RatUnifiedController::class, 'auditShow'])->name('show');
    });

    // ========================================================================
    // Relatos polimórficos por ocorrência (API JSON)
    // ========================================================================
    Route::prefix('ocorrencias/{ocorrencia}')->name('ocorrencias.')->group(function () {
        // Dados Gerais
        Route::get('/dados-gerais',         [RatUnifiedController::class, 'showDadosGerais'])->name('dados-gerais.show');
        Route::post('/dados-gerais',        [RatUnifiedController::class, 'storeDadosGerais'])->name('dados-gerais.store');
        Route::put('/dados-gerais/{id}',    [RatUnifiedController::class, 'updateDadosGerais'])->name('dados-gerais.update');

        // Envolvidos
        Route::get('/envolvidos',           [RatUnifiedController::class, 'indexEnvolvidos'])->name('envolvidos.index');
        Route::post('/envolvidos',          [RatUnifiedController::class, 'storeEnvolvidos'])->name('envolvidos.store');
        Route::put('/envolvidos/{id}',      [RatUnifiedController::class, 'updateEnvolvidos'])->name('envolvidos.update');
        Route::delete('/envolvidos/{id}',   [RatUnifiedController::class, 'destroyEnvolvidos'])->name('envolvidos.destroy');

        // Recursos
        Route::get('/recursos',             [RatUnifiedController::class, 'indexRecursos'])->name('recursos.index');
        Route::post('/recursos',            [RatUnifiedController::class, 'storeRecursos'])->name('recursos.store');
        Route::put('/recursos/{id}',        [RatUnifiedController::class, 'updateRecursos'])->name('recursos.update');
        Route::delete('/recursos/{id}',     [RatUnifiedController::class, 'destroyRecursos'])->name('recursos.destroy');

        // Vistoria
        Route::get('/vistoria',             [RatUnifiedController::class, 'showVistoria'])->name('vistoria.show');
        Route::post('/vistoria',            [RatUnifiedController::class, 'storeVistoria'])->name('vistoria.store');
        Route::put('/vistoria/{id}',        [RatUnifiedController::class, 'updateVistoria'])->name('vistoria.update');

        // Histórico
        Route::get('/historico',            [RatUnifiedController::class, 'showHistorico'])->name('historico.show');
        Route::post('/historico',           [RatUnifiedController::class, 'storeHistorico'])->name('historico.store');

        // Attachments
        Route::post('/attachments',         [RatUnifiedController::class, 'storeAttachment'])->name('attachments.store');
        Route::delete('/attachments/{id}',  [RatUnifiedController::class, 'destroyAttachment'])->name('attachments.destroy');
    });

    // ========================================================================
    // Power BI / JSON (com parâmetro {id} — após os grupos estáticos)
    // ========================================================================
    Route::get('/{id}/normalized', [RatUnifiedController::class, 'normalizedData'])->name('normalized');
    Route::get('/{id}/power-bi',   [RatUnifiedController::class, 'powerBiData'])->name('power-bi');
    Route::get('/{id}/json',       [RatUnifiedController::class, 'showJson'])->name('show-json');

    // ========================================================================
    // V1 Mobile API (compatibilidade retroativa)
    // ========================================================================
    Route::prefix('v1')->group(function () {
        Route::get('/ocorrencias/{id}/historico',        [RatUnifiedController::class, 'v1Timeline']);
        Route::get('/ocorrencias/{id}/historico/recent', [RatUnifiedController::class, 'v1Recent']);
        Route::post('/ocorrencias',                      [RatUnifiedController::class, 'v1Store']);
        Route::get('/protocolos',                        [RatUnifiedController::class, 'protocolProxyIndex']);
    });
});
