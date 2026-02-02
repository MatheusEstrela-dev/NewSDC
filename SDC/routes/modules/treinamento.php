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

// Já está dentro do middleware auth do web.php, então não precisa redefinir
Route::prefix('treinamentos')->name('treinamentos.')->group(function () {

    // Portal do Usuário - Visualizar treinamentos
    Route::get('/', TreinamentoIndexController::class)->name('index');
    Route::get('/export', \App\Modules\Treinamento\Presentation\Http\Controllers\TreinamentoExportController::class)->name('export');
    Route::get('/{id}', TreinamentoShowController::class)->name('show');

    // Admin - Gestão de Treinamentos
    Route::middleware('can:treinamento.manage')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::post('/', TreinamentoStoreController::class)->name('store');
        });
});
