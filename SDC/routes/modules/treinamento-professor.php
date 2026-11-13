<?php

use App\Modules\Treinamento\Controllers\Admin\ProfessorCandidatosController;
use App\Modules\Treinamento\Controllers\Admin\ProfessorDestroyController;
use App\Modules\Treinamento\Controllers\Admin\ProfessorStoreController;
use App\Modules\Treinamento\Controllers\Professor\AlunosController;
use App\Modules\Treinamento\Controllers\Professor\CapaDestroyController;
use App\Modules\Treinamento\Controllers\Professor\CapaStoreController;
use App\Modules\Treinamento\Controllers\Professor\ConteudoController;
use App\Modules\Treinamento\Controllers\Professor\CorrecaoStoreController;
use App\Modules\Treinamento\Controllers\Professor\CorrecoesController;
use App\Modules\Treinamento\Controllers\Professor\CursoDesativarController;
use App\Modules\Treinamento\Controllers\Professor\CursoPausarController;
use App\Modules\Treinamento\Controllers\Professor\CursoReativarController;
use App\Modules\Treinamento\Controllers\Professor\CursoRetomarController;
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
use App\Modules\Treinamento\Controllers\Professor\Prova\LiberacaoStoreController;
use App\Modules\Treinamento\Controllers\Professor\Prova\ProvasController;
use App\Modules\Treinamento\Controllers\Professor\Prova\ProvaShowController;
use App\Modules\Treinamento\Controllers\Professor\Prova\ProvaUpdateController;
use App\Modules\Treinamento\Controllers\Professor\Prova\QuestaoDestroyController;
use App\Modules\Treinamento\Controllers\Professor\Prova\QuestaoOrdemController;
use App\Modules\Treinamento\Controllers\Professor\Prova\QuestaoStoreController;
use App\Modules\Treinamento\Controllers\Professor\Prova\QuestaoUpdateController;
use App\Modules\Treinamento\Controllers\Professor\PublicarController;
use App\Modules\Treinamento\Controllers\Professor\Video\ItemVideoDesvincularController;
use App\Modules\Treinamento\Controllers\Professor\Video\ItemVideoVincularController;
use App\Modules\Treinamento\Controllers\Professor\Video\ReprocessarController;
use App\Modules\Treinamento\Controllers\Professor\Video\StatusController;
use App\Modules\Treinamento\Controllers\Professor\Video\UploadAbrirController;
use App\Modules\Treinamento\Controllers\Professor\Video\UploadCancelarController;
use App\Modules\Treinamento\Controllers\Professor\Video\UploadFinalizarController;
use App\Modules\Treinamento\Controllers\Professor\Video\UploadParteController;
use App\Modules\Treinamento\Controllers\Professor\Video\VideoDestroyController;
use App\Modules\Treinamento\Controllers\Professor\Video\VideoIndexController;
use App\Modules\Treinamento\Controllers\Professor\Video\VideoStoreController;
use App\Modules\Treinamento\Controllers\Professor\Video\VideoUpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Treinamento EAD - area do professor e designacao de professores
|--------------------------------------------------------------------------
| Incluido de dentro do grupo prefix('treinamentos')->name('treinamentos.')
| de routes/modules/treinamento.php, antes do wildcard /{id}.
*/

// Entra quem tem o acesso de professor ou quem edita cursos (admin gerencia qualquer curso EAD).
Route::prefix('professor')->name('professor.')->middleware('permission:treinamento.professor.acessar|treinamento.cursos.edit')->group(function () {
    Route::get('/', MeusCursosController::class)->name('index');

    // Biblioteca de videos: declarada antes das rotas com /{treinamento}.
    Route::get('/videos', VideoIndexController::class)->name('videos.index');
    Route::post('/videos', VideoStoreController::class)->name('videos.store');

    Route::middleware('gerencia.video')->prefix('/videos/{video}')->scopeBindings()->group(function () {
        Route::put('/', VideoUpdateController::class)->name('videos.update');
        Route::delete('/', VideoDestroyController::class)->name('videos.destroy');
        Route::get('/status', StatusController::class)->name('videos.status');
        Route::post('/reprocessar', ReprocessarController::class)->name('videos.reprocessar');
        Route::post('/upload', UploadAbrirController::class)->name('videos.upload.abrir');
        Route::put('/upload/{upload}/partes/{n}', UploadParteController::class)
            ->whereUuid('upload')->whereNumber('n')->name('videos.upload.parte');
        Route::post('/upload/{upload}/finalizar', UploadFinalizarController::class)
            ->whereUuid('upload')->name('videos.upload.finalizar');
        Route::delete('/upload/{upload}', UploadCancelarController::class)
            ->whereUuid('upload')->name('videos.upload.cancelar');
    });

    Route::middleware('leciona.curso')->group(function () {
        Route::get('/{treinamento}/conteudo', ConteudoController::class)->name('conteudo');
        Route::get('/{treinamento}/alunos', AlunosController::class)->name('alunos');
        Route::get('/{treinamento}/correcoes', CorrecoesController::class)->name('correcoes');
        Route::post('/{treinamento}/entregas/{entrega}/correcao', CorrecaoStoreController::class)->name('entregas.correcao');
        Route::get('/{treinamento}/entregas/{entrega}/arquivo', EntregaArquivoController::class)->name('entregas.arquivo');
        Route::get('/{treinamento}/previa', PreviaController::class)->name('previa');
        Route::get('/{treinamento}/provas', ProvasController::class)->name('provas');
        Route::get('/{treinamento}/provas/{prova}', ProvaShowController::class)->whereNumber('prova')->name('provas.show');
        Route::put('/{treinamento}/provas/{prova}', ProvaUpdateController::class)->whereNumber('prova')->name('provas.update');
        Route::post('/{treinamento}/provas/{prova}/questoes', QuestaoStoreController::class)->whereNumber('prova')->name('provas.questoes.store');
        Route::put('/{treinamento}/provas/{prova}/questoes/{questao}', QuestaoUpdateController::class)->whereNumber(['prova', 'questao'])->name('provas.questoes.update');
        Route::delete('/{treinamento}/provas/{prova}/questoes/{questao}', QuestaoDestroyController::class)->whereNumber(['prova', 'questao'])->name('provas.questoes.destroy');
        Route::post('/{treinamento}/provas/{prova}/questoes-ordem', QuestaoOrdemController::class)->whereNumber('prova')->name('provas.questoes.ordem');
        Route::post('/{treinamento}/provas/{prova}/liberacoes', LiberacaoStoreController::class)->whereNumber('prova')->name('provas.liberacoes.store');
        Route::post('/{treinamento}/publicar', PublicarController::class)->name('publicar');
        Route::post('/{treinamento}/capa', CapaStoreController::class)->name('capa.store');
        Route::delete('/{treinamento}/capa', CapaDestroyController::class)->name('capa.destroy');

        Route::post('/{treinamento}/pastas', PastaStoreController::class)->name('pastas.store');
        Route::put('/{treinamento}/pastas/{modulo}', PastaUpdateController::class)->name('pastas.update');
        Route::delete('/{treinamento}/pastas/{modulo}', PastaDestroyController::class)->name('pastas.destroy');
        Route::post('/{treinamento}/pastas-ordem', PastaReordenarController::class)->name('pastas.ordem');

        Route::post('/{treinamento}/pastas/{modulo}/itens', ItemStoreController::class)->name('itens.store');
        Route::post('/{treinamento}/pastas/{modulo}/itens-ordem', ItemReordenarController::class)->name('itens.ordem');
        Route::post('/{treinamento}/itens/{item}', ItemUpdateController::class)->name('itens.update');
        Route::delete('/{treinamento}/itens/{item}', ItemDestroyController::class)->name('itens.destroy');
        Route::get('/{treinamento}/itens/{item}/arquivo', ItemArquivoController::class)->name('itens.arquivo');
        Route::put('/{treinamento}/itens/{item}/video', ItemVideoVincularController::class)->name('itens.video.vincular');
        Route::delete('/{treinamento}/itens/{item}/video', ItemVideoDesvincularController::class)->name('itens.video.desvincular');
    });
});

// Ciclo do curso: professor designado ou admin (que nao precisa da area do professor).
Route::prefix('professor')->name('professor.')->middleware('gerencia.ciclo.curso')->group(function () {
    Route::post('/{treinamento}/pausar', CursoPausarController::class)->name('pausar');
    Route::post('/{treinamento}/retomar', CursoRetomarController::class)->name('retomar');
    Route::post('/{treinamento}/desativar', CursoDesativarController::class)->name('desativar');
    Route::post('/{treinamento}/reativar', CursoReativarController::class)->name('reativar');
});

// Designacao de professores (administracao do curso).
Route::middleware('can:treinamento.cursos.edit')->group(function () {
    Route::get('/professores/candidatos', ProfessorCandidatosController::class)->name('professores.candidatos');
    Route::post('/{treinamento}/professores', ProfessorStoreController::class)->name('professores.store');
    Route::delete('/{treinamento}/professores/{userId}', ProfessorDestroyController::class)->name('professores.destroy');
});
