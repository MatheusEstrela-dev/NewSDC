<?php

use App\Modules\Plantao\Controllers\EscalaAssumirController;
use App\Modules\Plantao\Controllers\EscalaDestroyController;
use App\Modules\Plantao\Controllers\EscalaIndexController;
use App\Modules\Plantao\Controllers\EscalaItemDestroyController;
use App\Modules\Plantao\Controllers\EscalaItemStoreController;
use App\Modules\Plantao\Controllers\EscalaItemUpdateController;
use App\Modules\Plantao\Controllers\EscalaPublicarController;
use App\Modules\Plantao\Controllers\EscalaStoreController;
use App\Modules\Plantao\Controllers\ChaveCheckinController;
use App\Modules\Plantao\Controllers\ChaveCheckoutController;
use App\Modules\Plantao\Controllers\ChaveScanController;
use App\Modules\Plantao\Controllers\ChaveScanPageController;
use App\Modules\Plantao\Controllers\MovimentacaoRetornoController;
use App\Modules\Plantao\Controllers\MovimentacaoSaidaController;
use App\Modules\Plantao\Controllers\NoticiasIndexController;
use App\Modules\Plantao\Controllers\PassagemAbrirController;
use App\Modules\Plantao\Controllers\PassagemAceitarController;
use App\Modules\Plantao\Controllers\PassagemEncerrarController;
use App\Modules\Plantao\Controllers\PlantaoDestroyController;
use App\Modules\Plantao\Controllers\PlantaoEditController;
use App\Modules\Plantao\Controllers\PlantaoExportController;
use App\Modules\Plantao\Controllers\PlantaoIndexController;
use App\Modules\Plantao\Controllers\PlantaoShowController;
use App\Modules\Plantao\Controllers\PlantaoUpdateController;
use App\Modules\Plantao\Controllers\PlantonistaDestroyController;
use App\Modules\Plantao\Controllers\PlantonistaIndexController;
use App\Modules\Plantao\Controllers\PlantonistaStoreController;
use App\Modules\Plantao\Controllers\PlantonistaUpdateController;
use App\Modules\Plantao\Controllers\RelatorioPassagemController;
use App\Modules\Plantao\Controllers\ReservaCancelarController;
use App\Modules\Plantao\Controllers\ReservaIndexController;
use App\Modules\Plantao\Controllers\ReservaStoreController;
use App\Modules\Plantao\Controllers\ViaturaDestroyController;
use App\Modules\Plantao\Controllers\ViaturaQrCodeController;
use App\Modules\Plantao\Controllers\ViaturaQrCodeRotacionarController;
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

        // Etiqueta do chaveiro. Sob `manage` e nao `view`: quem imprime a
        // etiqueta define qual token abre a chave daquela viatura, e
        // ?rotacionar=1 invalida as etiquetas ja coladas.
        Route::get('/{viatura}/qrcode', ViaturaQrCodeController::class)
            ->name('qrcode')
            ->middleware('can:plantao.reservas.manage');

        // Troca da etiqueta em POST: o ato mata o adesivo vigente e nao pode
        // ser repetido por prefetch nem por recarregamento, como acontecia
        // quando era `?rotacionar=1` no GET acima.
        Route::post('/{viatura}/qrcode/rotacionar', ViaturaQrCodeRotacionarController::class)
            ->name('qrcode.rotacionar')
            ->middleware('can:plantao.reservas.manage');
    });

    // ─── Reservas de viatura ────────────────────────────────────────────────
    //
    // Subgrupo estatico: entra ANTES das parametrizadas /{plantao} pela mesma
    // razao de /viaturas e /escala -- senao "reservas" casa como id de plantao.
    Route::prefix('reservas')->name('reservas.')->group(function () {
        Route::get('/', ReservaIndexController::class)
            ->name('index')
            ->middleware('can:plantao.reservas.view');

        Route::post('/', ReservaStoreController::class)
            ->name('store')
            ->middleware('can:plantao.reservas.create');

        // Middleware exige apenas `create`, que todo agente tem: cancelar a
        // PROPRIA reserva e operacao diaria. Derrubar a de outra pessoa exige
        // `manage`, e essa checagem fina mora no controller e responde 403 --
        // mesmo desenho do PlantaoDestroyController.
        Route::post('/{reserva}/cancelar', ReservaCancelarController::class)
            ->name('cancelar')
            ->middleware('can:plantao.reservas.create');
    });

    // ─── Chave (QR Code) ────────────────────────────────────────────────────
    //
    // Retirar e devolver chave e movimentar viatura: reusa
    // `plantao.viaturas.movimentar` em vez de criar slug novo. O que a reserva
    // acrescenta e QUEM pode, nao um direito diferente.
    Route::prefix('chave')->name('chave.')->group(function () {
        Route::get('/scan', ChaveScanPageController::class)
            ->name('scan')
            ->middleware('can:plantao.viaturas.movimentar');

        // Leitura do token: responde JSON e nao grava nada.
        Route::post('/scan', ChaveScanController::class)
            ->name('scan.resolver')
            ->middleware('can:plantao.viaturas.movimentar');

        Route::post('/{reserva}/checkin', ChaveCheckinController::class)
            ->name('checkin')
            ->middleware('can:plantao.viaturas.movimentar');

        Route::post('/{reserva}/checkout', ChaveCheckoutController::class)
            ->name('checkout')
            ->middleware('can:plantao.viaturas.movimentar');
    });

    // ─── Escala ─────────────────────────────────────────────────────────────
    //
    // Subgrupo estatico: entra ANTES das parametrizadas /{plantao}, senao
    // "escala" seria casado como um id de plantao e a tela morreria em 404.
    Route::prefix('escala')->name('escala.')->group(function () {

        // /itens/... vem antes de /{escala} pela MESMA razao, um nivel abaixo:
        // sem isto, "itens" casa como {escala} e o binding estoura.
        Route::put('/itens/{item}', EscalaItemUpdateController::class)
            ->name('itens.update')
            ->middleware('can:plantao.escala.edit');

        Route::delete('/itens/{item}', EscalaItemDestroyController::class)
            ->name('itens.destroy')
            ->middleware('can:plantao.escala.edit');

        // Assumir usa a permissao de ABRIR TURNO, nao a de escala: a acao e
        // abertura de plantao, e a escala so pre-preenche os campos. Quem pode
        // abrir turno pelo botao normal pode assumir a propria vaga.
        Route::post('/itens/{item}/assumir', EscalaAssumirController::class)
            ->name('itens.assumir')
            ->middleware('can:plantao.turnos.create');

        Route::get('/', EscalaIndexController::class)
            ->name('index')
            ->middleware('can:plantao.escala.view');

        Route::post('/', EscalaStoreController::class)
            ->name('store')
            ->middleware('can:plantao.escala.create');

        Route::post('/{escala}/itens', EscalaItemStoreController::class)
            ->name('itens.store')
            ->middleware('can:plantao.escala.edit');

        Route::post('/{escala}/publicar', EscalaPublicarController::class)
            ->name('publicar')
            ->middleware('can:plantao.escala.publicar');

        Route::delete('/{escala}', EscalaDestroyController::class)
            ->name('destroy')
            ->middleware('can:plantao.escala.edit');
    });

    // ─── Plantonistas (quem pode ser escalado) ──────────────────────────────
    Route::prefix('plantonistas')->name('plantonistas.')->group(function () {
        Route::get('/', PlantonistaIndexController::class)
            ->name('index')
            ->middleware('can:plantao.plantonistas.manage');

        Route::post('/', PlantonistaStoreController::class)
            ->name('store')
            ->middleware('can:plantao.plantonistas.manage');

        Route::put('/{plantonista}', PlantonistaUpdateController::class)
            ->name('update')
            ->middleware('can:plantao.plantonistas.manage');

        Route::delete('/{plantonista}', PlantonistaDestroyController::class)
            ->name('destroy')
            ->middleware('can:plantao.plantonistas.manage');
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

    // Exclusao suave. Slug proprio e mais restrito que `edit`: hoje so
    // super-admin e admin o tem, porque o turno carrega aceite formal de duas
    // partes e tirar isso da listagem nao e ato de operacao diaria.
    Route::delete('/{plantao}', PlantaoDestroyController::class)
        ->name('destroy')
        ->middleware('can:plantao.turnos.delete');

    Route::get('/', PlantaoIndexController::class)
        ->name('index')
        ->middleware('can:plantao.turnos.view');
});
