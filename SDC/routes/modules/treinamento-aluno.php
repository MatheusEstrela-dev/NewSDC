<?php

use App\Modules\Treinamento\Controllers\Aluno\EntregaStoreController;
use App\Modules\Treinamento\Controllers\Aluno\ItemArquivoController;
use App\Modules\Treinamento\Controllers\Aluno\ProgressoController;
use App\Modules\Treinamento\Controllers\Aluno\SalaDeAulaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Treinamento EAD - aluno servidor
|--------------------------------------------------------------------------
| Incluido de dentro do grupo prefix('treinamentos')->name('treinamentos.')
| de routes/modules/treinamento.php, antes do wildcard /{id}.
*/

Route::prefix('aluno')->name('aluno.')->middleware('can:treinamento.cursos.view')->group(function () {
    Route::get('/cursos/{treinamento}', SalaDeAulaController::class)->name('sala');
    Route::get('/cursos/{treinamento}/itens/{item}/arquivo', ItemArquivoController::class)->name('itens.arquivo');
    Route::post('/cursos/{treinamento}/itens/{item}/progresso', ProgressoController::class)
        ->middleware('throttle:120,1')
        ->name('itens.progresso');
    Route::post('/cursos/{treinamento}/itens/{item}/entregas', EntregaStoreController::class)
        ->middleware('throttle:10,1')
        ->name('itens.entregas');
});
