<?php

use App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoIndexController;
use App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoShowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Decretações
|--------------------------------------------------------------------------
*/

// Já está dentro do middleware auth do web.php, então não precisa redefinir
Route::prefix('decretacoes')->name('decretacoes.')->group(function () {

    // Index - Lista de processos
    Route::get('/', ProcessoIndexController::class)->name('index');

    // Export - Exportar CSV
    Route::get('/export', \App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoExportController::class)->name('export');

    // Show - Visualizar processo
    Route::get('/{id}', ProcessoShowController::class)->name('show');

    // TODO: Adicionar rotas de create, update, delete quando controllers estiverem prontos
    // Route::get('/create', ProcessoCreateController::class)->name('create');
    // Route::post('/', ProcessoStoreController::class)->name('store');
    // Route::get('/{id}/edit', ProcessoEditController::class)->name('edit');
    // Route::put('/{id}', ProcessoUpdateController::class)->name('update');
    // Route::delete('/{id}', ProcessoDeleteController::class)->name('delete');
});
