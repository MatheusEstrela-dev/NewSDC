<?php

use App\Modules\Decretacoes\Controllers\DecretacoesController;
use Illuminate\Support\Facades\Route;

Route::prefix('decretacoes')->name('decretacoes.')->group(function () {

    Route::get('/', [DecretacoesController::class, 'index'])
        ->name('index')
        ->middleware('can:decretacoes.processos.view');

    Route::get('/export', [DecretacoesController::class, 'exportPowerBI'])
        ->name('export')
        ->middleware('can:decretacoes.processos.export');

    Route::get('/create', [DecretacoesController::class, 'create'])
        ->name('create')
        ->middleware('can:decretacoes.processos.create');

    Route::post('/', [DecretacoesController::class, 'store'])
        ->name('store')
        ->middleware('can:decretacoes.processos.create');

    Route::get('/{id}', [DecretacoesController::class, 'show'])
        ->name('show')
        ->middleware('can:decretacoes.processos.view');

    Route::get('/{id}/edit', [DecretacoesController::class, 'edit'])
        ->name('edit')
        ->middleware('can:decretacoes.processos.edit');

    Route::put('/{id}', [DecretacoesController::class, 'update'])
        ->name('update')
        ->middleware('can:decretacoes.processos.edit');

    Route::delete('/{id}', [DecretacoesController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:decretacoes.processos.delete');

    Route::post('/{id}/desastres', [DecretacoesController::class, 'storeDesastres'])
        ->name('desastres.store')
        ->middleware('can:decretacoes.processos.edit');

    // API Routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/', [DecretacoesController::class, 'apiIndex'])
            ->name('index')
            ->middleware('can:decretacoes.processos.view');

        Route::get('/{id}', [DecretacoesController::class, 'apiShow'])
            ->name('show')
            ->middleware('can:decretacoes.processos.view');
    });
});
