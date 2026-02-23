<?php

use App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoIndexController;
use App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoShowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Decretações
|--------------------------------------------------------------------------
*/

// Ja esta dentro do middleware auth do web.php, entao nao precisa redefinir
Route::prefix('decretacoes')->name('decretacoes.')->group(function () {

    // Index - Lista de processos
    Route::get('/', ProcessoIndexController::class)
        ->name('index')
        ->middleware('can:decretacoes.processos.view');

    // Export - Exportar CSV
    Route::get('/export', \App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoExportController::class)
        ->name('export')
        ->middleware('can:decretacoes.processos.export');

    // Create - Novo processo (ANTES da rota {id} para nao conflitar)
    Route::get('/create', \App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoCreateController::class)
        ->name('create')
        ->middleware('can:decretacoes.processos.create');

    // Store - Salvar novo processo (POST)
    Route::post('/', \App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoStoreController::class)
        ->name('store')
        ->middleware('can:decretacoes.processos.create');

    // Show - Visualizar processo
    Route::get('/{id}', ProcessoShowController::class)
        ->name('show')
        ->middleware('can:decretacoes.processos.view');

    // TODO: Adicionar rotas de edit, update, delete quando controllers estiverem prontos
    // Route::get('/{id}/edit', ProcessoEditController::class)->name('edit')->middleware('can:decretacoes.processos.edit');
    // Route::put('/{id}', ProcessoUpdateController::class)->name('update')->middleware('can:decretacoes.processos.edit');
    // Route::delete('/{id}', ProcessoDeleteController::class)->name('delete')->middleware('can:decretacoes.processos.delete');
});
