<?php

use App\Http\Controllers\Compdec\RatController as CompdecRatController;
use App\Http\Controllers\Compdec\BoRatController;
use App\Http\Controllers\Compdec\RatAlvoController;
use App\Http\Controllers\Compdec\RatOcorrenciaController;
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

    Route::get('/alvos/{id}', [RatAlvoController::class, 'show'])
        ->name('alvos.show')
        ->middleware('can:rat.protocolos.view');

    // Ocorrências (CRUD completo da nova estrutura)
    Route::get('/ocorrencias', [RatOcorrenciaController::class, 'index'])
        ->name('ocorrencias.index')
        ->middleware('can:rat.protocolos.view');

    Route::get('/ocorrencias/{id}', [RatOcorrenciaController::class, 'show'])
        ->name('ocorrencias.show')
        ->middleware('can:rat.protocolos.view');

    Route::post('/ocorrencias', [RatOcorrenciaController::class, 'store'])
        ->name('ocorrencias.store')
        ->middleware('can:rat.protocolos.create');

    Route::patch('/ocorrencias/{id}/finalizar', [RatOcorrenciaController::class, 'finalize'])
        ->name('ocorrencias.finalize')
        ->middleware('can:rat.protocolos.finalize');

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

    Route::get('/{id}', [CompdecRatController::class, 'show'])
        ->name('show')
        ->middleware('can:rat.protocolos.view');

    Route::get('/{id}/edit', [CompdecRatController::class, 'edit'])
        ->name('edit')
        ->middleware('can:rat.protocolos.edit');

    Route::put('/{id}', [CompdecRatController::class, 'update'])
        ->name('update')
        ->middleware('can:rat.protocolos.edit');

    Route::delete('/{id}', [CompdecRatController::class, 'destroy'])
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

    Route::get('/{id}/edit', [RatController::class, 'show'])
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

