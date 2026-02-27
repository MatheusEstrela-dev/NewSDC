<?php

use App\Modules\Compdec\Controllers\OrgaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('compdec')->name('compdec.')->group(function () {

    Route::get('/orgaos', [OrgaoController::class, 'index'])->name('index');
    Route::get('/orgaos/{id}', [OrgaoController::class, 'show'])
        ->name('show')
        ->where('id', '[0-9]+');

    Route::middleware('can:compdec.manage')->group(function () {
        Route::get('/orgaos/novo', [OrgaoController::class, 'create'])->name('create');
        Route::post('/orgaos', [OrgaoController::class, 'store'])->name('store');
        Route::get('/orgaos/{id}/editar', [OrgaoController::class, 'edit'])
            ->name('edit')
            ->where('id', '[0-9]+');
        Route::put('/orgaos/{id}', [OrgaoController::class, 'update'])
            ->name('update')
            ->where('id', '[0-9]+');
        Route::delete('/orgaos/{id}', [OrgaoController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9]+');
        Route::post('/orgaos/{id}/usuarios', [OrgaoController::class, 'vincularUsuario'])
            ->name('usuarios.vincular')
            ->where('id', '[0-9]+');
    });
});
