<?php

use App\Modules\Treinamento\Controllers\Gestao\CancelarController;
use App\Modules\Treinamento\Controllers\Gestao\CronogramaDestroyController;
use App\Modules\Treinamento\Controllers\Gestao\CronogramaIndexController;
use App\Modules\Treinamento\Controllers\Gestao\CronogramaStoreController;
use App\Modules\Treinamento\Controllers\Gestao\CronogramaUpdateController;
use App\Modules\Treinamento\Controllers\Gestao\IndexController;
use App\Modules\Treinamento\Controllers\Gestao\PeriodoUpdateController;
use App\Modules\Treinamento\Controllers\Gestao\ShowController;
use App\Modules\Treinamento\Controllers\Gestao\TurmaDestroyController;
use App\Modules\Treinamento\Controllers\Gestao\TurmaStoreController;
use App\Modules\Treinamento\Controllers\Gestao\TurmaUpdateController;
use App\Modules\Treinamento\Controllers\Gestao\VagasUpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Area do Gestor do curso (turmas, vagas, periodo, cronograma,
| historico e cancelamento). Issue #86, bloco 2.
|--------------------------------------------------------------------------
|
| Incluido direto em routes/web.php, dentro do grupo auth, logo apos
| routes/modules/treinamento.php: aquele arquivo e de outro bloco (seguranca)
| e nao ganha um novo require.
|
| O prefixo de URL e "treinamentos-gestor" (segmento proprio, com hifen) de
| PROPOSITO, nao "treinamentos/gestor": routes/modules/treinamento.php tem um
| wildcard aberto `/{id}` sem whereNumber (TreinamentoShowController, so com
| treinamento.cursos.view) registrado ANTES deste arquivo na ordem de
| inclusao. Qualquer rota em /treinamentos/<algo-nao-numerico> cairia nesse
| wildcard primeiro, trocando a permissao exigida sem aviso. Um primeiro
| segmento proprio elimina a colisao de vez, sem depender da ordem de
| inclusao nem exigir tocar em treinamento.php.
|
| Nomes de rota continuam "treinamentos.gestor.*" (Sidebar.vue e
| config/permissions.php ja esperam esse nome, independente da URL).
|
| Leitura: treinamento.gestor.acessar. Escrita estrutural (turma, vagas,
| periodo, cronograma): treinamento.cursos.configurar. Cancelar:
| treinamento.cursos.cancelar.
*/
Route::prefix('treinamentos-gestor')
    ->name('treinamentos.gestor.')
    ->middleware('can:treinamento.gestor.acessar')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/{treinamento}', ShowController::class)->whereNumber('treinamento')->name('show');
        Route::get('/{treinamento}/cronograma', CronogramaIndexController::class)->whereNumber('treinamento')->name('cronograma.index');

        Route::middleware('can:treinamento.cursos.configurar')->group(function () {
            Route::post('/{treinamento}/turmas', TurmaStoreController::class)->whereNumber('treinamento')->name('turmas.store');
            Route::put('/{treinamento}/turmas/{turma}', TurmaUpdateController::class)->whereNumber(['treinamento', 'turma'])->name('turmas.update');
            Route::delete('/{treinamento}/turmas/{turma}', TurmaDestroyController::class)->whereNumber(['treinamento', 'turma'])->name('turmas.destroy');
            Route::put('/{treinamento}/vagas', VagasUpdateController::class)->whereNumber('treinamento')->name('vagas.update');
            Route::put('/{treinamento}/periodo', PeriodoUpdateController::class)->whereNumber('treinamento')->name('periodo.update');
            Route::post('/{treinamento}/cronograma', CronogramaStoreController::class)->whereNumber('treinamento')->name('cronograma.store');
            Route::put('/{treinamento}/cronograma/{item}', CronogramaUpdateController::class)->whereNumber(['treinamento', 'item'])->name('cronograma.update');
            Route::delete('/{treinamento}/cronograma/{item}', CronogramaDestroyController::class)->whereNumber(['treinamento', 'item'])->name('cronograma.destroy');
        });

        Route::post('/{treinamento}/cancelar', CancelarController::class)
            ->whereNumber('treinamento')
            ->middleware('can:treinamento.cursos.cancelar')
            ->name('cancelar');
    });
