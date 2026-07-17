<?php

use App\Modules\Rat\Controllers\RatUnifiedController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RAT — Controller único: RatUnifiedController
//
// Autorizacao por permissao (padrao do PAE): cada rota exige o can: da policy
// RAT. Papeis (RatPermissionsSeeder): leitor-rat = view/historico.view;
// analista-rat = operacional; coordenador-rat/super-admin = tudo.
//   - leitura              -> rat.protocolos.view
//   - criacao              -> rat.protocolos.create
//   - edicao               -> rat.protocolos.edit
//   - exclusao             -> rat.protocolos.delete
//   - finalizar            -> rat.protocolos.finalizar
//   - export               -> rat.protocolos.export
//   - estatisticas/BI      -> rat.bi.view
//   - relatos (write)      -> rat.relatos.manage
//   - historico (leitura)  -> rat.historico.view
//   - API mobile v1        -> rat.api.access
// ============================================================================

Route::prefix('rat')->name('rat.')->group(function () {

    // ── Rotas literais devem vir ANTES das wildcards ─────────────────────────

    // Web (Inertia)
    Route::get('/',       [RatUnifiedController::class, 'index'])->name('index')->middleware('can:rat.protocolos.view');
    Route::get('/create', [RatUnifiedController::class, 'create'])->name('create')->middleware('can:rat.protocolos.create');
    Route::post('/',      [RatUnifiedController::class, 'store'])->name('store')->middleware('can:rat.protocolos.create');

    // Export / Statistics
    Route::get('/export',       [RatUnifiedController::class, 'export'])->name('export')->middleware('can:rat.protocolos.export');
    Route::get('/export/async', [RatUnifiedController::class, 'exportAsync'])->name('export.async')->middleware('can:rat.protocolos.export');
    Route::get('/export-rats',  [RatUnifiedController::class, 'exportRats'])->name('export-rats')->middleware('can:rat.protocolos.export');
    Route::get('/statistics',   [RatUnifiedController::class, 'statistics'])->name('statistics')->middleware('can:rat.bi.view');

    // Boletim de Ocorrência
    Route::get('/bo',       [RatUnifiedController::class, 'indexBo'])->name('bo.index')->middleware('can:rat.protocolos.view');
    Route::post('/bo',      [RatUnifiedController::class, 'storeBo'])->name('bo.store')->middleware('can:rat.protocolos.create');
    Route::get('/bo/{id}',  [RatUnifiedController::class, 'showBo'])->name('bo.show')->middleware('can:rat.protocolos.view');

    // V1 Mobile API
    Route::prefix('v1')->middleware('can:rat.api.access')->group(function () {
        Route::get('/ocorrencias/{id}/historico',         [RatUnifiedController::class, 'v1Timeline']);
        Route::get('/ocorrencias/{id}/historico/recent',  [RatUnifiedController::class, 'v1Recent']);
        Route::post('/ocorrencias',                       [RatUnifiedController::class, 'v1Store']);
        Route::get('/protocolos',                         [RatUnifiedController::class, 'protocolProxyIndex']);
    });

    // ── Wildcards com {id} ───────────────────────────────────────────────────
    Route::post('/{id}/relacionar', [RatUnifiedController::class, 'createRelacionado'])->name('relacionar.store')->middleware('can:rat.protocolos.edit');
    Route::get('/{id}/print',      [RatUnifiedController::class, 'print'])->name('print')->middleware('can:rat.protocolos.view');
    Route::get('/{id}/edit',     [RatUnifiedController::class, 'edit'])->name('edit')->middleware('can:rat.protocolos.edit');
    Route::get('/{id}/json',     [RatUnifiedController::class, 'showJson'])->name('show-json')->middleware('can:rat.protocolos.view');
    Route::put('/{id}',          [RatUnifiedController::class, 'update'])->name('update')->middleware('can:rat.protocolos.edit');
    Route::patch('/{id}',        [RatUnifiedController::class, 'update'])->middleware('can:rat.protocolos.edit');
    Route::delete('/{id}',       [RatUnifiedController::class, 'destroy'])->name('destroy')->middleware('can:rat.protocolos.delete');
    Route::patch('/{id}/finalize', [RatUnifiedController::class, 'finalize'])->name('finalize')->middleware('can:rat.protocolos.finalizar');
    Route::match(['post', 'patch'], '/{id}/draft', [RatUnifiedController::class, 'draft'])->name('draft')->middleware('can:rat.protocolos.edit');
    Route::get('/{id}',          [RatUnifiedController::class, 'show'])->name('show')->middleware('can:rat.protocolos.view');

    // ── Relatos polimórficos por ocorrência ──────────────────────────────────
    Route::prefix('/{ocorrencia}')->name('ocorrencias.')->group(function () {

        Route::get('/dados-gerais',        [RatUnifiedController::class, 'showDadosGerais'])->name('dados-gerais.show')->middleware('can:rat.protocolos.view');
        Route::post('/dados-gerais',       [RatUnifiedController::class, 'storeDadosGerais'])->name('dados-gerais.store')->middleware('can:rat.relatos.manage');
        Route::put('/dados-gerais/{id}',   [RatUnifiedController::class, 'updateDadosGerais'])->name('dados-gerais.update')->middleware('can:rat.relatos.manage');

        Route::get('/envolvidos',          [RatUnifiedController::class, 'indexEnvolvidos'])->name('envolvidos.index')->middleware('can:rat.protocolos.view');
        Route::post('/envolvidos',         [RatUnifiedController::class, 'storeEnvolvidos'])->name('envolvidos.store')->middleware('can:rat.relatos.manage');
        Route::put('/envolvidos/{id}',     [RatUnifiedController::class, 'updateEnvolvidos'])->name('envolvidos.update')->middleware('can:rat.relatos.manage');
        Route::delete('/envolvidos/{id}',  [RatUnifiedController::class, 'destroyEnvolvidos'])->name('envolvidos.destroy')->middleware('can:rat.relatos.manage');

        Route::get('/recursos',            [RatUnifiedController::class, 'indexRecursos'])->name('recursos.index')->middleware('can:rat.protocolos.view');
        Route::post('/recursos',           [RatUnifiedController::class, 'storeRecursos'])->name('recursos.store')->middleware('can:rat.relatos.manage');
        Route::put('/recursos/{id}',       [RatUnifiedController::class, 'updateRecursos'])->name('recursos.update')->middleware('can:rat.relatos.manage');
        Route::delete('/recursos/{id}',    [RatUnifiedController::class, 'destroyRecursos'])->name('recursos.destroy')->middleware('can:rat.relatos.manage');

        Route::get('/vistoria',            [RatUnifiedController::class, 'showVistoria'])->name('vistoria.show')->middleware('can:rat.protocolos.view');
        Route::post('/vistoria',           [RatUnifiedController::class, 'storeVistoria'])->name('vistoria.store')->middleware('can:rat.relatos.manage');
        Route::put('/vistoria/{id}',       [RatUnifiedController::class, 'updateVistoria'])->name('vistoria.update')->middleware('can:rat.relatos.manage');

        Route::get('/historico',           [RatUnifiedController::class, 'showHistorico'])->name('historico.show')->middleware('can:rat.historico.view');
        Route::post('/historico',          [RatUnifiedController::class, 'storeHistorico'])->name('historico.store')->middleware('can:rat.relatos.manage');

        Route::post('/attachments',        [RatUnifiedController::class, 'storeAttachment'])->name('attachments.store')->middleware('can:rat.protocolos.edit');
        Route::get('/attachments/{id}',    [RatUnifiedController::class, 'showAttachment'])->name('attachments.show')->middleware('can:rat.protocolos.view');
        Route::delete('/attachments/{id}', [RatUnifiedController::class, 'destroyAttachment'])->name('attachments.destroy')->middleware('can:rat.protocolos.edit');
    });
});
