<?php

use App\Modules\Rat\Controllers\RatAttachmentController;
use App\Modules\Rat\Controllers\RatController;
use App\Modules\Rat\Controllers\RatDataController;
use App\Modules\Rat\Controllers\RatFinalizeController;
use App\Modules\Rat\Controllers\RatWriteController;
use Illuminate\Support\Facades\Route;

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

