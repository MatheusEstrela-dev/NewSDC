<?php

use App\Modules\Demandas\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {

    Route::get('/demandas', [TaskController::class, 'index'])
        ->name('demandas.index')
        ->middleware('can:demandas.chamados.view');

    Route::get('/demandas/nova', [TaskController::class, 'create'])
        ->name('demandas.create')
        ->middleware('can:demandas.chamados.create');

    Route::post('/demandas', [TaskController::class, 'store'])
        ->name('demandas.store')
        ->middleware('can:demandas.chamados.create');

    Route::get('/demandas/{id}', [TaskController::class, 'show'])
        ->name('demandas.show')
        ->middleware('can:demandas.chamados.view');

    Route::post('/demandas/{id}/comentarios', [TaskController::class, 'addComment'])
        ->name('demandas.comments.store')
        ->middleware('can:demandas.chamados.view');

    Route::prefix('admin/demandas')->name('admin.demandas.')->group(function () {

        Route::get('/export', [TaskController::class, 'export'])
            ->name('export')
            ->middleware('can:demandas.chamados.export');

        Route::get('/', [TaskController::class, 'adminIndex'])
            ->name('index')
            ->middleware('can:demandas.chamados.manage');

        Route::post('/{id}/atribuir', [TaskController::class, 'assign'])
            ->name('assign')
            ->middleware('can:demandas.chamados.manage');

        Route::post('/{id}/status', [TaskController::class, 'changeStatus'])
            ->name('change-status')
            ->middleware('can:demandas.chamados.edit');

        Route::put('/{id}', [TaskController::class, 'update'])
            ->name('update')
            ->middleware('can:demandas.chamados.edit');

        Route::delete('/{id}', [TaskController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:demandas.chamados.delete');
    });
});
