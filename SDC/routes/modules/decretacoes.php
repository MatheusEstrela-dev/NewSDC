<?php

use App\Modules\Decretacoes\Controllers\ProcessoController;
use Illuminate\Support\Facades\Route;

Route::prefix('decretacoes')->name('decretacoes.')->group(function () {

    Route::get('/', [ProcessoController::class, 'index'])
        ->name('index')
        ->middleware('can:decretacoes.processos.view');

    Route::get('/export', [ProcessoController::class, 'export'])
        ->name('export')
        ->middleware('can:decretacoes.processos.export');

    Route::get('/create', [ProcessoController::class, 'create'])
        ->name('create')
        ->middleware('can:decretacoes.processos.create');

    Route::post('/', [ProcessoController::class, 'store'])
        ->name('store')
        ->middleware('can:decretacoes.processos.create');

    Route::get('/{id}', [ProcessoController::class, 'show'])
        ->name('show')
        ->middleware('can:decretacoes.processos.view');
});
