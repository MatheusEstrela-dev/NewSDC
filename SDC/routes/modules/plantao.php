<?php

use App\Modules\Plantao\Controllers\MovimentacaoRetornoController;
use App\Modules\Plantao\Controllers\MovimentacaoSaidaController;
use App\Modules\Plantao\Controllers\NoticiasIndexController;
use App\Modules\Plantao\Controllers\PassagemAbrirController;
use App\Modules\Plantao\Controllers\PassagemAceitarController;
use App\Modules\Plantao\Controllers\PassagemEncerrarController;
use App\Modules\Plantao\Controllers\PlantaoEditController;
use App\Modules\Plantao\Controllers\PlantaoExportController;
use App\Modules\Plantao\Controllers\PlantaoIndexController;
use App\Modules\Plantao\Controllers\PlantaoShowController;
use App\Modules\Plantao\Controllers\PlantaoUpdateController;
use App\Modules\Plantao\Controllers\RelatorioPassagemController;
use App\Modules\Plantao\Controllers\ViaturaDestroyController;
use App\Modules\Plantao\Controllers\ViaturaIndexController;
use App\Modules\Plantao\Controllers\ViaturaStoreController;
use App\Modules\Plantao\Controllers\ViaturaUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('plantao')->name('plantao.')->group(function () {

    // Rotas estaticas primeiro: /viaturas nao pode ser capturada como {plantao}.
    Route::get('/export', PlantaoExportController::class)
        ->name('export')
        ->middleware('can:plantao.turnos.export');

    Route::get('/noticias', NoticiasIndexController::class)
        ->name('noticias')
        ->middleware('can:plantao.turnos.view');

    // Abertura de turno: entrada da maquina de estados da passagem (spec 4).
    // Estatica, entao vem antes das parametrizadas /{plantao}/... - senao
    // "abrir" seria casado como {plantao}.
    Route::post('/abrir', PassagemAbrirController::class)
        ->name('passagem.abrir')
        ->middleware('can:plantao.turnos.create');

    Route::prefix('viaturas')->name('viaturas.')->group(function () {
        Route::get('/', ViaturaIndexController::class)
            ->name('index')
            ->middleware('can:plantao.viaturas.view');

        Route::post('/', ViaturaStoreController::class)
            ->name('store')
            ->middleware('can:plantao.viaturas.create');

        Route::put('/{viatura}', ViaturaUpdateController::class)
            ->name('update')
            ->middleware('can:plantao.viaturas.edit');

        Route::delete('/{viatura}', ViaturaDestroyController::class)
            ->name('destroy')
            ->middleware('can:plantao.viaturas.delete');

        Route::post('/{viatura}/saida', MovimentacaoSaidaController::class)
            ->name('saida')
            ->middleware('can:plantao.viaturas.movimentar');
    });

    Route::post('/movimentacoes/{movimentacao}/retorno', MovimentacaoRetornoController::class)
        ->name('movimentacoes.retorno')
        ->middleware('can:plantao.viaturas.movimentar');

    // Rotas parametrizadas /{plantao}/...: precisam vir DEPOIS das estaticas
    // e do subgrupo /viaturas, senao Laravel casa "viaturas" como {plantao}.
    Route::post('/{plantao}/encerrar', PassagemEncerrarController::class)
        ->name('passagem.encerrar')
        ->middleware('can:plantao.passagem.encerrar');

    Route::post('/{plantao}/aceitar', PassagemAceitarController::class)
        ->name('passagem.aceitar')
        ->middleware('can:plantao.passagem.aceitar');

    Route::get('/{plantao}/relatorio', RelatorioPassagemController::class)
        ->name('passagem.relatorio')
        ->middleware('can:plantao.passagem.relatorio');

    // /{plantao}/edit antes do /{plantao} bare word, no mesmo espirito das
    // estaticas acima (nao ha colisao real - segmentos diferentes -, mas
    // mantem o padrao de "mais especifico primeiro" documentado neste
    // arquivo). Middleware de rota so garante a permissao base; a checagem
    // fina de dono+ATIVO (com excecao de supervisao) mora no controller e
    // responde 403, nao 404/422.
    Route::get('/{plantao}/edit', PlantaoEditController::class)
        ->name('edit')
        ->middleware('can:plantao.turnos.edit');

    Route::get('/{plantao}', PlantaoShowController::class)
        ->name('show')
        ->middleware('can:plantao.turnos.view');

    Route::put('/{plantao}', PlantaoUpdateController::class)
        ->name('update')
        ->middleware('can:plantao.turnos.edit');

    Route::get('/', PlantaoIndexController::class)
        ->name('index')
        ->middleware('can:plantao.turnos.view');
});
