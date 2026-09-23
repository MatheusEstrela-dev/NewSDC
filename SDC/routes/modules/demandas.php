<?php

use App\Modules\Demandas\Controllers\DemandaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {

    Route::get('/demandas', [DemandaController::class, 'index'])
        ->name('demandas.index')
        ->middleware('can:demandas.chamados.view');

    Route::get('/demandas/nova', [DemandaController::class, 'create'])
        ->name('demandas.create')
        ->middleware('can:demandas.chamados.create');

    Route::post('/demandas', [DemandaController::class, 'store'])
        ->name('demandas.store')
        ->middleware('can:demandas.chamados.create');

    Route::get('/demandas/{id}', [DemandaController::class, 'show'])
        ->name('demandas.show')
        ->middleware('can:demandas.chamados.view');

    Route::post('/demandas/{id}/comentarios', [DemandaController::class, 'addComment'])
        ->name('demandas.comments.store')
        ->middleware('can:demandas.chamados.view');

    Route::prefix('admin/demandas')->name('admin.demandas.')->group(function () {

        Route::get('/export', [DemandaController::class, 'export'])
            ->name('export')
            ->middleware('can:demandas.chamados.export');

        Route::get('/', [DemandaController::class, 'adminIndex'])
            ->name('index')
            ->middleware('can:demandas.chamados.manage');

        Route::post('/{id}/atribuir', [DemandaController::class, 'assign'])
            ->name('assign')
            ->middleware('can:demandas.chamados.manage');

        Route::post('/{id}/status', [DemandaController::class, 'changeStatus'])
            ->name('change-status')
            ->middleware('can:demandas.chamados.edit');

        Route::put('/{id}', [DemandaController::class, 'update'])
            ->name('update')
            ->middleware('can:demandas.chamados.edit');

        Route::delete('/{id}', [DemandaController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:demandas.chamados.delete');
    });
});
