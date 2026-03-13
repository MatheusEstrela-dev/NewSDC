<?php

use App\Http\Controllers\Compdec\BoRatController;
use App\Http\Controllers\Compdec\RatAlvoController;
use App\Http\Controllers\Compdec\RatController as CompdecRatController;
use App\Http\Controllers\Compdec\RatDadosGeraisController;
use App\Http\Controllers\Compdec\RatEnvolvidosController;
use App\Http\Controllers\Compdec\RatOcorrenciaController;
use App\Http\Controllers\Compdec\RatRecursoController;
use App\Http\Controllers\Compdec\RatVistoriaController;
use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Controllers\RatAttachmentController;
use App\Modules\Rat\Controllers\RatController;
use App\Modules\Rat\Controllers\RatDataController;
use App\Modules\Rat\Controllers\RatFinalizeController;
use App\Modules\Rat\Controllers\RatWriteController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RAT — Nova Estrutura (Compdec): RatOcorrencia + relatos polimórficos
// ============================================================================

Route::prefix('compdec/rat')->name('compdec.rat.')->group(function () {

    // Boletim de Ocorrência
    Route::get('/bo', [BoRatController::class, 'index'])
        ->name('bo.index')
        ->middleware('can:rat.protocolos.view');

    Route::post('/bo', [BoRatController::class, 'store'])
        ->name('bo.store')
        ->middleware('can:rat.protocolos.create');

    // Alvos (Endereços/Locais de interesse)
    Route::get('/alvos', [RatAlvoController::class, 'index'])
        ->name('alvos.index')
        ->middleware('can:rat.protocolos.view');

    Route::get('/alvos/{ocorrencia}', [RatAlvoController::class, 'show'])
        ->name('alvos.show')
        ->middleware('can:rat.protocolos.view');

    // Ocorrências (CRUD completo da nova estrutura)
    Route::get('/ocorrencias', [RatOcorrenciaController::class, 'index'])
        ->name('ocorrencias.index')
        ->middleware('can:rat.protocolos.view');

    Route::get('/ocorrencias/{ocorrencia}', [RatOcorrenciaController::class, 'show'])
        ->name('ocorrencias.show')
        ->middleware('can:rat.protocolos.view');

    Route::post('/ocorrencias', [RatOcorrenciaController::class, 'store'])
        ->name('ocorrencias.store')
        ->middleware('can:rat.protocolos.create');

    Route::patch('/ocorrencias/{ocorrencia}/finalizar', [RatOcorrenciaController::class, 'finalize'])
        ->name('ocorrencias.finalize')
        ->middleware('can:rat.protocolos.finalize');

    // ========================================================================
    // Relatos polimórficos por ocorrência
    // Permissão: rat.relatos.manage (create/edit/delete) + rat.protocolos.view (read)
    // ========================================================================
    Route::prefix('/ocorrencias/{ocorrencia}')->name('ocorrencias.')->group(function () {

        // Dados Gerais
        Route::get('/dados-gerais', [RatDadosGeraisController::class, 'show'])
            ->name('dados-gerais.show')
            ->middleware('can:rat.protocolos.view');

        Route::post('/dados-gerais', [RatDadosGeraisController::class, 'store'])
            ->name('dados-gerais.store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/dados-gerais/{dadosGerais}', [RatDadosGeraisController::class, 'update'])
            ->name('dados-gerais.update')
            ->middleware('can:rat.relatos.manage');

        // Envolvidos
        Route::get('/envolvidos', [RatEnvolvidosController::class, 'index'])
            ->name('envolvidos.index')
            ->middleware('can:rat.protocolos.view');

        Route::post('/envolvidos', [RatEnvolvidosController::class, 'store'])
            ->name('envolvidos.store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/envolvidos/{envolvido}', [RatEnvolvidosController::class, 'update'])
            ->name('envolvidos.update')
            ->middleware('can:rat.relatos.manage');

        Route::delete('/envolvidos/{envolvido}', [RatEnvolvidosController::class, 'destroy'])
            ->name('envolvidos.destroy')
            ->middleware('can:rat.relatos.manage');

        // Recursos empregados
        Route::get('/recursos', [RatRecursoController::class, 'index'])
            ->name('recursos.index')
            ->middleware('can:rat.protocolos.view');

        Route::post('/recursos', [RatRecursoController::class, 'store'])
            ->name('recursos.store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/recursos/{recurso}', [RatRecursoController::class, 'update'])
            ->name('recursos.update')
            ->middleware('can:rat.relatos.manage');

        Route::delete('/recursos/{recurso}', [RatRecursoController::class, 'destroy'])
            ->name('recursos.destroy')
            ->middleware('can:rat.relatos.manage');

        // Vistoria técnica
        Route::get('/vistoria', [RatVistoriaController::class, 'show'])
            ->name('vistoria.show')
            ->middleware('can:rat.protocolos.view');

        Route::post('/vistoria', [RatVistoriaController::class, 'store'])
            ->name('vistoria.store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/vistoria/{vistoria}', [RatVistoriaController::class, 'update'])
            ->name('vistoria.update')
            ->middleware('can:rat.relatos.manage');
    });

    // Controller principal (listagem, criação, edição, exportação)
    Route::get('/export', [CompdecRatController::class, 'exportRats'])
        ->name('export')
        ->middleware('can:rat.protocolos.export');

    Route::get('/', [CompdecRatController::class, 'index'])
        ->name('index')
        ->middleware('can:rat.protocolos.view');

    Route::get('/create', [CompdecRatController::class, 'create'])
        ->name('create')
        ->middleware('can:rat.protocolos.create');

    Route::post('/', [CompdecRatController::class, 'store'])
        ->name('store')
        ->middleware('can:rat.protocolos.create');

    Route::patch('/{ocorrencia}/finalizar', [CompdecRatController::class, 'finalize'])
        ->name('finalize')
        ->middleware('can:rat.protocolos.finalize');

    Route::get('/{ocorrencia}', [CompdecRatController::class, 'show'])
        ->name('show')
        ->middleware('can:rat.protocolos.view');

    Route::get('/{ocorrencia}/edit', [CompdecRatController::class, 'edit'])
        ->name('edit')
        ->middleware('can:rat.protocolos.edit');

    Route::put('/{ocorrencia}', [CompdecRatController::class, 'update'])
        ->name('update')
        ->middleware('can:rat.protocolos.edit');

    Route::delete('/{ocorrencia}', [CompdecRatController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:rat.protocolos.delete');
});

// ============================================================================
// RAT — Estrutura Legada (Módulo): rats UUID + JSON columns
// ============================================================================

Route::prefix('rat')->name('rat.')->group(function () {
    // Exportação CSV e dados JSON — separados no RatDataController (SRP)
    Route::get('/export', [RatDataController::class, 'export'])
        ->name('export')
        ->middleware('can:rat.protocolos.export');

    Route::get('/', [RatController::class, 'index'])
        ->name('index')
        ->middleware('can:rat.protocolos.view');

    Route::get('/create', [RatController::class, 'create'])
        ->name('create')
        ->middleware('can:rat.protocolos.create');

    Route::post('/', [RatController::class, 'store'])
        ->name('store')
        ->middleware('can:rat.protocolos.create');

    Route::get('/{id}/json', [RatDataController::class, 'showJson'])
        ->name('show.json')
        ->middleware('can:rat.protocolos.view');

    Route::get('/{id}/edit', [RatController::class, 'edit'])
        ->name('edit')
        ->middleware('can:rat.protocolos.edit');

    Route::put('/{id}', [RatWriteController::class, 'update'])
        ->name('update')
        ->middleware('can:rat.protocolos.edit');

    Route::patch('/{id}/draft', [RatWriteController::class, 'draft'])
        ->name('draft')
        ->middleware('can:rat.protocolos.edit');

    // Finalização: SRP — ação única delegada ao RatFinalizeController (invokable)
    Route::patch('/{id}/finalize', RatFinalizeController::class)
        ->name('finalize')
        ->middleware('can:rat.protocolos.finalize');

    Route::get('/{id}', [RatController::class, 'show'])
        ->name('show')
        ->middleware('can:rat.protocolos.view');

    Route::delete('/{id}', [RatController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:rat.protocolos.delete');

    // Gerenciamento de anexos (upload multipart + remoção)
    Route::post('/{id}/attachments', [RatAttachmentController::class, 'store'])
        ->name('attachments.store')
        ->middleware('can:rat.protocolos.edit');

    Route::delete('/{id}/attachments/{anexoId}', [RatAttachmentController::class, 'destroy'])
        ->name('attachments.destroy')
        ->middleware('can:rat.protocolos.edit');
});

