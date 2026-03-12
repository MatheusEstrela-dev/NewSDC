<?php

use App\Modules\Rat\Controllers\RatController;
use App\Modules\Rat\Controllers\RatDataController;
use App\Modules\Rat\Controllers\RatFinalizeController;
use App\Modules\Rat\Controllers\RatWriteController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RAT  Módulo único: rat_ocorrencias
// Prefixo: compdec/rat  |  Nome: compdec.rat.*
// ============================================================================

Route::prefix('compdec/rat')->name('compdec.rat.')->middleware(['auth'])->group(function () {

    // Exportação CSV
    Route::get('/export', [RatDataController::class, 'export'])
        ->name('export')
        ->middleware('can:rat.protocolos.export');

    // --- CRUD principal ---

    Route::get('/', [RatController::class, 'index'])
        ->name('index')
        ->middleware('can:rat.protocolos.view');

    Route::get('/create', [RatController::class, 'create'])
        ->name('create')
        ->middleware('can:rat.protocolos.create');

    Route::post('/', [RatController::class, 'store'])
        ->name('store')
        ->middleware('can:rat.protocolos.create');

    Route::get('/{id}', [RatController::class, 'show'])
        ->name('show')
        ->middleware('can:rat.protocolos.view');

    Route::get('/{id}/edit', [RatController::class, 'edit'])
        ->name('edit')
        ->middleware('can:rat.protocolos.edit');

    Route::put('/{id}', [RatController::class, 'update'])
        ->name('update')
        ->middleware('can:rat.protocolos.edit');

    // Salva rascunho (mantém status = 0)
    Route::patch('/{id}/draft', [RatWriteController::class, 'draft'])
        ->name('draft')
        ->middleware('can:rat.protocolos.edit');

    // Finaliza a ocorrência (status = 1)
    Route::patch('/{id}/finalizar', RatFinalizeController::class)
        ->name('finalize')
        ->middleware('can:rat.protocolos.finalize');

    Route::delete('/{id}', [RatController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:rat.protocolos.delete');

    // JSON para impressão
    Route::get('/{id}/json', [RatDataController::class, 'showJson'])
        ->name('show.json')
        ->middleware('can:rat.protocolos.view');
});