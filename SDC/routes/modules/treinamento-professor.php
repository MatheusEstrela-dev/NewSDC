<?php

use App\Modules\Treinamento\Controllers\Admin\ProfessorCandidatosController;
use App\Modules\Treinamento\Controllers\Admin\ProfessorDestroyController;
use App\Modules\Treinamento\Controllers\Admin\ProfessorStoreController;
use App\Modules\Treinamento\Controllers\Professor\AlunosController;
use App\Modules\Treinamento\Controllers\Professor\ConteudoController;
use App\Modules\Treinamento\Controllers\Professor\CorrecaoStoreController;
use App\Modules\Treinamento\Controllers\Professor\CorrecoesController;
use App\Modules\Treinamento\Controllers\Professor\EntregaArquivoController;
use App\Modules\Treinamento\Controllers\Professor\ItemArquivoController;
use App\Modules\Treinamento\Controllers\Professor\ItemDestroyController;
use App\Modules\Treinamento\Controllers\Professor\ItemReordenarController;
use App\Modules\Treinamento\Controllers\Professor\ItemStoreController;
use App\Modules\Treinamento\Controllers\Professor\ItemUpdateController;
use App\Modules\Treinamento\Controllers\Professor\MeusCursosController;
use App\Modules\Treinamento\Controllers\Professor\PastaDestroyController;
use App\Modules\Treinamento\Controllers\Professor\PastaReordenarController;
use App\Modules\Treinamento\Controllers\Professor\PastaStoreController;
use App\Modules\Treinamento\Controllers\Professor\PastaUpdateController;
use App\Modules\Treinamento\Controllers\Professor\PreviaController;
use App\Modules\Treinamento\Controllers\Professor\PublicarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Treinamento EAD - area do professor e designacao de professores
|--------------------------------------------------------------------------
| Incluido de dentro do grupo prefix('treinamentos')->name('treinamentos.')
| de routes/modules/treinamento.php, antes do wildcard /{id}.
*/

Route::prefix('professor')->name('professor.')->middleware('can:treinamento.professor.acessar')->group(function () {
    Route::get('/', MeusCursosController::class)->name('index');

    Route::middleware('leciona.curso')->group(function () {
        Route::get('/{treinamento}/conteudo', ConteudoController::class)->name('conteudo');
        Route::get('/{treinamento}/alunos', AlunosController::class)->name('alunos');
        Route::get('/{treinamento}/correcoes', CorrecoesController::class)->name('correcoes');
        Route::post('/{treinamento}/entregas/{entrega}/correcao', CorrecaoStoreController::class)->name('entregas.correcao');
        Route::get('/{treinamento}/entregas/{entrega}/arquivo', EntregaArquivoController::class)->name('entregas.arquivo');
        Route::get('/{treinamento}/previa', PreviaController::class)->name('previa');
        Route::post('/{treinamento}/publicar', PublicarController::class)->name('publicar');

        Route::post('/{treinamento}/pastas', PastaStoreController::class)->name('pastas.store');
        Route::put('/{treinamento}/pastas/{modulo}', PastaUpdateController::class)->name('pastas.update');
        Route::delete('/{treinamento}/pastas/{modulo}', PastaDestroyController::class)->name('pastas.destroy');
        Route::post('/{treinamento}/pastas-ordem', PastaReordenarController::class)->name('pastas.ordem');

        Route::post('/{treinamento}/pastas/{modulo}/itens', ItemStoreController::class)->name('itens.store');
        Route::post('/{treinamento}/pastas/{modulo}/itens-ordem', ItemReordenarController::class)->name('itens.ordem');
        Route::post('/{treinamento}/itens/{item}', ItemUpdateController::class)->name('itens.update');
        Route::delete('/{treinamento}/itens/{item}', ItemDestroyController::class)->name('itens.destroy');
        Route::get('/{treinamento}/itens/{item}/arquivo', ItemArquivoController::class)->name('itens.arquivo');
    });
});

// Designacao de professores (administracao do curso).
Route::middleware('can:treinamento.cursos.edit')->group(function () {
    Route::get('/professores/candidatos', ProfessorCandidatosController::class)->name('professores.candidatos');
    Route::post('/{treinamento}/professores', ProfessorStoreController::class)->name('professores.store');
    Route::delete('/{treinamento}/professores/{userId}', ProfessorDestroyController::class)->name('professores.destroy');
});
