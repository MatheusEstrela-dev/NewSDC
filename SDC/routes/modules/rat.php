<?php

use App\Modules\Rat\Controllers\RatAttachmentController;
use App\Modules\Rat\Controllers\RatController;
use App\Modules\Rat\Controllers\RatFinalizeController;
use App\Modules\Rat\Controllers\RatWriteController;
use Illuminate\Support\Facades\Route;

Route::prefix('rat')->name('rat.')->group(function () {

    // -------------------------------------------------------------------------
    // Read-only — Inertia pages & JSON
    // -------------------------------------------------------------------------

    Route::get('/', [RatController::class, 'index'])
        ->name('index')
        ->middleware('can:rat.protocolos.view');

    Route::get('/export', [RatController::class, 'export'])
        ->name('export')
        ->middleware('can:rat.protocolos.export');

    Route::get('/create', [RatController::class, 'create'])
        ->name('create')
        ->middleware('can:rat.protocolos.create');

    Route::get('/{id}/json', [RatController::class, 'showJson'])
        ->name('show.json')
        ->middleware('can:rat.protocolos.view');

    Route::get('/{id}/edit', [RatController::class, 'edit'])
        ->name('edit')
        ->middleware('can:rat.protocolos.edit');

    Route::get('/{id}', [RatController::class, 'show'])
        ->name('show')
        ->middleware('can:rat.protocolos.view');

    // -------------------------------------------------------------------------
    // Write — create, update, draft, finalize, delete
    // -------------------------------------------------------------------------

    Route::post('/', [RatWriteController::class, 'store'])
        ->name('store')
        ->middleware('can:rat.protocolos.create');

    Route::put('/{id}', [RatWriteController::class, 'update'])
        ->name('update')
        ->middleware('can:rat.protocolos.edit');

    Route::patch('/{id}/draft', [RatWriteController::class, 'draft'])
        ->name('draft')
        ->middleware('can:rat.protocolos.edit');

    Route::patch('/{id}/finalize', RatFinalizeController::class)
        ->name('finalize')
        ->middleware('can:rat.protocolos.finalize');

    Route::delete('/{id}', [RatController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:rat.protocolos.delete');

    // -------------------------------------------------------------------------
    // Imagens/Arquivos
    // -------------------------------------------------------------------------

    Route::post('/{id}/imagens', [RatAttachmentController::class, 'store'])
        ->name('imagens.store')
        ->middleware('can:rat.protocolos.edit');

    Route::delete('/{id}/imagens/{imagemId}', [RatAttachmentController::class, 'destroy'])
        ->name('imagens.destroy')
        ->middleware('can:rat.protocolos.edit');
});
