<?php

use App\Modules\Rat\Controllers\RatController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('rat')->name('rat.')->group(function () {
    Route::post('/sync', [RatController::class, 'sync'])->name('sync');

    Route::get('/export', [RatController::class, 'export'])
        ->name('export')
        ->middleware('can:rat.protocolos.export');

    Route::get('/', [RatController::class, 'index'])
        ->name('index')
        ->middleware('can:rat.protocolos.view');

    Route::get('/create', [RatController::class, 'create'])
        ->name('create')
        ->middleware('can:rat.protocolos.create');

    Route::get('/{id}/json', [RatController::class, 'showJson'])
        ->name('show.json')
        ->middleware('can:rat.protocolos.view');

    Route::get('/{id}/edit', [RatController::class, 'edit'])
        ->name('edit')
        ->middleware('can:rat.protocolos.edit');

    Route::patch('/{id}/finalize', [RatController::class, 'finalize'])
        ->name('finalize')
        ->middleware('can:rat.protocolos.finalize');

    Route::get('/{id}', [RatController::class, 'show'])
        ->name('show')
        ->middleware('can:rat.protocolos.view');

    Route::delete('/{id}', [RatController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:rat.protocolos.delete');
});
