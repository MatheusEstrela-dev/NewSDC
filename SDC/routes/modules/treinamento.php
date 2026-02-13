<?php

use App\Modules\Treinamento\Presentation\Http\Controllers\TreinamentoIndexController;
use App\Modules\Treinamento\Presentation\Http\Controllers\TreinamentoShowController;
use App\Modules\Treinamento\Presentation\Http\Controllers\TreinamentoStoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Treinamento
|--------------------------------------------------------------------------
*/

// Ja esta dentro do middleware auth do web.php, entao nao precisa redefinir
Route::prefix('treinamentos')->name('treinamentos.')->group(function () {

    // Portal do Usuario - Visualizar treinamentos
    Route::get('/', TreinamentoIndexController::class)
        ->name('index')
        ->middleware('can:treinamento.cursos.view');
    Route::get('/export', \App\Modules\Treinamento\Presentation\Http\Controllers\TreinamentoExportController::class)
        ->name('export')
        ->middleware('can:treinamento.cursos.export');
    Route::get('/{id}', TreinamentoShowController::class)
        ->name('show')
        ->middleware('can:treinamento.cursos.view');

    // Admin - Gestao de Treinamentos
    Route::middleware('can:treinamento.cursos.create')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::post('/', TreinamentoStoreController::class)->name('store');
        });
});
