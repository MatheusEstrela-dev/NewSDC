<?php

use App\Modules\Treinamento\Controllers\Aluno\EntregaStoreController;
use App\Modules\Treinamento\Controllers\Aluno\ItemArquivoController;
use App\Modules\Treinamento\Controllers\Aluno\MeusCursosController;
use App\Modules\Treinamento\Controllers\Aluno\ProgressoController;
use App\Modules\Treinamento\Controllers\Aluno\ProvaEnviarController;
use App\Modules\Treinamento\Controllers\Aluno\ProvaIniciarController;
use App\Modules\Treinamento\Controllers\Aluno\ProvaResponderController;
use App\Modules\Treinamento\Controllers\Aluno\ProvaShowController;
use App\Modules\Treinamento\Controllers\Aluno\SalaDeAulaController;
use App\Modules\Treinamento\Controllers\SessaoPulsoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Treinamento EAD - aluno servidor
|--------------------------------------------------------------------------
| Incluido de dentro do grupo prefix('treinamentos')->name('treinamentos.')
| de routes/modules/treinamento.php, antes do wildcard /{id}.
*/

// Pulso do player de video: renova a sessao (as rotas /videos/* so leem a sessao).
Route::post('/sessao/pulso', SessaoPulsoController::class)
    ->middleware('throttle:30,1')
    ->name('sessao.pulso');

Route::prefix('aluno')->name('aluno.')->middleware('can:treinamento.cursos.view')->group(function () {
    Route::get('/', MeusCursosController::class)->name('index');
    Route::get('/cursos/{treinamento}', SalaDeAulaController::class)->name('sala');
    Route::get('/cursos/{treinamento}/itens/{item}/arquivo', ItemArquivoController::class)->name('itens.arquivo');
    Route::post('/cursos/{treinamento}/itens/{item}/progresso', ProgressoController::class)
        ->middleware('throttle:120,1')
        ->name('itens.progresso');
    Route::post('/cursos/{treinamento}/itens/{item}/entregas', EntregaStoreController::class)
        ->middleware('throttle:10,1')
        ->name('itens.entregas');

    // Prova do item: so a regra do backend decide se abre, quantas tentativas restam e a nota.
    Route::prefix('/cursos/{treinamento}/itens/{item}/prova')->name('itens.prova')->group(function () {
        Route::get('/', ProvaShowController::class);
        Route::post('/tentativas', ProvaIniciarController::class)->name('.iniciar');
        Route::put('/tentativas/{tentativa}/respostas', ProvaResponderController::class)
            ->middleware('throttle:120,1')
            ->name('.responder');
        Route::post('/tentativas/{tentativa}/enviar', ProvaEnviarController::class)
            ->middleware('throttle:10,1')
            ->name('.enviar');
    });
});
