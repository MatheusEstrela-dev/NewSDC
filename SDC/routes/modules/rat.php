<?php

use App\Modules\Rat\Controllers\RatUnifiedController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RAT — Controller único: RatUnifiedController
// ============================================================================

Route::prefix('rat')->name('rat.')->group(function () {

    // ── Rotas literais devem vir ANTES das wildcards ─────────────────────────

    // Web (Inertia)
    Route::get('/',       [RatUnifiedController::class, 'index'])->name('index');
    Route::get('/create', [RatUnifiedController::class, 'create'])->name('create');
    Route::post('/',      [RatUnifiedController::class, 'store'])->name('store');

    // Export / Statistics
    Route::get('/export',       [RatUnifiedController::class, 'export'])->name('export');
    Route::get('/export/async', [RatUnifiedController::class, 'exportAsync'])->name('export.async');
    Route::get('/export-rats',  [RatUnifiedController::class, 'exportRats'])->name('export-rats');
    Route::get('/statistics',   [RatUnifiedController::class, 'statistics'])->name('statistics');

    // Boletim de Ocorrência
    Route::get('/bo',       [RatUnifiedController::class, 'indexBo'])->name('bo.index');
    Route::post('/bo',      [RatUnifiedController::class, 'storeBo'])->name('bo.store');
    Route::get('/bo/{id}',  [RatUnifiedController::class, 'showBo'])->name('bo.show');

    // V1 Mobile API
    Route::prefix('v1')->group(function () {
        Route::get('/ocorrencias/{id}/historico',         [RatUnifiedController::class, 'v1Timeline']);
        Route::get('/ocorrencias/{id}/historico/recent',  [RatUnifiedController::class, 'v1Recent']);
        Route::post('/ocorrencias',                       [RatUnifiedController::class, 'v1Store']);
        Route::get('/protocolos',                         [RatUnifiedController::class, 'protocolProxyIndex']);
    });

    // ── Wildcards com {id} ───────────────────────────────────────────────────
    Route::post('/{id}/relacionar', [RatUnifiedController::class, 'createRelacionado'])->name('relacionar.store');
    Route::get('/{id}/edit',     [RatUnifiedController::class, 'edit'])->name('edit');
    Route::get('/{id}/json',     [RatUnifiedController::class, 'showJson'])->name('show-json');
    Route::put('/{id}',          [RatUnifiedController::class, 'update'])->name('update');
    Route::patch('/{id}',        [RatUnifiedController::class, 'update']);
    Route::delete('/{id}',       [RatUnifiedController::class, 'destroy'])->name('destroy');
    Route::patch('/{id}/finalize', [RatUnifiedController::class, 'finalize'])->name('finalize');
    Route::match(['post', 'patch'], '/{id}/draft', [RatUnifiedController::class, 'draft'])->name('draft');
    Route::get('/{id}',          [RatUnifiedController::class, 'show'])->name('show');

    // ── Relatos polimórficos por ocorrência ──────────────────────────────────
    Route::prefix('/{ocorrencia}')->name('ocorrencias.')->group(function () {

        Route::get('/dados-gerais',        [RatUnifiedController::class, 'showDadosGerais'])->name('dados-gerais.show');
        Route::post('/dados-gerais',       [RatUnifiedController::class, 'storeDadosGerais'])->name('dados-gerais.store');
        Route::put('/dados-gerais/{id}',   [RatUnifiedController::class, 'updateDadosGerais'])->name('dados-gerais.update');

        Route::get('/envolvidos',          [RatUnifiedController::class, 'indexEnvolvidos'])->name('envolvidos.index');
        Route::post('/envolvidos',         [RatUnifiedController::class, 'storeEnvolvidos'])->name('envolvidos.store');
        Route::put('/envolvidos/{id}',     [RatUnifiedController::class, 'updateEnvolvidos'])->name('envolvidos.update');
        Route::delete('/envolvidos/{id}',  [RatUnifiedController::class, 'destroyEnvolvidos'])->name('envolvidos.destroy');

        Route::get('/recursos',            [RatUnifiedController::class, 'indexRecursos'])->name('recursos.index');
        Route::post('/recursos',           [RatUnifiedController::class, 'storeRecursos'])->name('recursos.store');
        Route::put('/recursos/{id}',       [RatUnifiedController::class, 'updateRecursos'])->name('recursos.update');
        Route::delete('/recursos/{id}',    [RatUnifiedController::class, 'destroyRecursos'])->name('recursos.destroy');

        Route::get('/vistoria',            [RatUnifiedController::class, 'showVistoria'])->name('vistoria.show');
        Route::post('/vistoria',           [RatUnifiedController::class, 'storeVistoria'])->name('vistoria.store');
        Route::put('/vistoria/{id}',       [RatUnifiedController::class, 'updateVistoria'])->name('vistoria.update');

        Route::get('/historico',           [RatUnifiedController::class, 'showHistorico'])->name('historico.show');
        Route::post('/historico',          [RatUnifiedController::class, 'storeHistorico'])->name('historico.store');

        Route::post('/attachments',        [RatUnifiedController::class, 'storeAttachment'])->name('attachments.store');
        Route::delete('/attachments/{id}', [RatUnifiedController::class, 'destroyAttachment'])->name('attachments.destroy');
    });
});
