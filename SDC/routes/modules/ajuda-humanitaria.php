<?php

use App\Modules\AjudaHumanitaria\Controllers\AnexoPedidoController;
use App\Modules\AjudaHumanitaria\Controllers\BeneficiarioController;
use App\Modules\AjudaHumanitaria\Controllers\EntradaAhController;
use App\Modules\AjudaHumanitaria\Controllers\EstoqueAhController;
use App\Modules\AjudaHumanitaria\Controllers\ItemPedidoController;
use App\Modules\AjudaHumanitaria\Controllers\LiberacaoAhController;
use App\Modules\AjudaHumanitaria\Controllers\ParecerController;
use App\Modules\AjudaHumanitaria\Controllers\PedidoAhController;
use App\Modules\AjudaHumanitaria\Controllers\PedidoAhDashboardController;
use App\Modules\AjudaHumanitaria\Controllers\PrestacaoContaController;
use App\Modules\AjudaHumanitaria\Controllers\TramitacaoController;
use App\Modules\AjudaHumanitaria\Controllers\TransferenciaAhController;
use Illuminate\Support\Facades\Route;

Route::prefix('ajuda-humanitaria')->name('ajuda-humanitaria.')->group(function () {

    Route::get('/', [PedidoAhDashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('can:humanitaria.pedidos.view');

    // Pedido de Material de Ajuda Humanitaria (MAH).
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/', [PedidoAhController::class, 'index'])
            ->name('index')
            ->middleware('can:humanitaria.pedidos.view');

        // Declarada antes de /{id} para nao ser capturada como id do pedido.
        Route::get('/create', [PedidoAhController::class, 'create'])
            ->name('create')
            ->middleware('can:humanitaria.pedidos.create');

        Route::post('/', [PedidoAhController::class, 'store'])
            ->name('store')
            ->middleware('can:humanitaria.pedidos.create');

        Route::get('/{id}', [PedidoAhController::class, 'show'])
            ->name('show')
            ->middleware('can:humanitaria.pedidos.view')
            ->whereNumber('id');

        // Materiais do pedido. A permissao exigida varia com o tipo do item e
        // e resolvida no StoreItemPedidoRequest, por isso a rota nao fixa uma.
        Route::post('/{id}/itens', [ItemPedidoController::class, 'store'])
            ->name('itens.store')
            ->whereNumber('id');

        Route::delete('/{id}/itens/{item}', [ItemPedidoController::class, 'destroy'])
            ->name('itens.destroy')
            ->whereNumber(['id', 'item']);

        Route::post('/{id}/tramitar', [TramitacaoController::class, 'store'])
            ->name('tramitar')
            ->middleware('can:humanitaria.pedidos.tramitar')
            ->whereNumber('id');

        // Pareceres tecnicos.
        Route::post('/{id}/pareceres', [ParecerController::class, 'store'])
            ->name('pareceres.store')
            ->middleware('can:humanitaria.pedidos.parecer')
            ->whereNumber('id');

        Route::delete('/{id}/pareceres/{parecer}', [ParecerController::class, 'destroy'])
            ->name('pareceres.destroy')
            ->middleware('can:humanitaria.pedidos.parecer')
            ->whereNumber(['id', 'parecer']);

        // Anexos. O download exige apenas leitura; anexar e remover exigem a
        // permissao propria de anexos.
        Route::post('/{id}/anexos', [AnexoPedidoController::class, 'store'])
            ->name('anexos.store')
            ->middleware('can:humanitaria.pedidos.anexos')
            ->whereNumber('id');

        Route::get('/{id}/anexos/{media}', [AnexoPedidoController::class, 'download'])
            ->name('anexos.download')
            ->middleware('can:humanitaria.pedidos.view')
            ->whereNumber(['id', 'media']);

        Route::delete('/{id}/anexos/{media}', [AnexoPedidoController::class, 'destroy'])
            ->name('anexos.destroy')
            ->middleware('can:humanitaria.pedidos.anexos')
            ->whereNumber(['id', 'media']);

        // Prestacao de contas. A prestacao em si nasce como efeito da entrada
        // do pedido em Atendido, nao por rota.
        Route::post('/{id}/entregas', [PrestacaoContaController::class, 'storeEntrega'])
            ->name('entregas.store')
            ->middleware('can:humanitaria.prestacao.lancar')
            ->whereNumber('id');

        Route::delete('/{id}/entregas/{entrega}', [PrestacaoContaController::class, 'destroyEntrega'])
            ->name('entregas.destroy')
            ->middleware('can:humanitaria.prestacao.lancar')
            ->whereNumber(['id', 'entrega']);

        Route::post('/{id}/homologar', [PrestacaoContaController::class, 'homologar'])
            ->name('homologar')
            ->middleware('can:humanitaria.prestacao.homologar')
            ->whereNumber('id');
    });

    // Consulta do estoque (RN-25). Somente leitura: a escrita no ledger e
    // feita por RegistrarMovimentoEstoque, a partir das operacoes do modulo.
    Route::prefix('estoque')->name('estoque.')->group(function () {
        // Declarada antes de / para nao ser capturada por uma futura rota
        // com parametro.
        Route::get('/export', [EstoqueAhController::class, 'export'])
            ->name('export')
            ->middleware('can:humanitaria.saldo.view');

        Route::get('/', [EstoqueAhController::class, 'index'])
            ->name('index')
            ->middleware('can:humanitaria.saldo.view');
    });

    // Liberacoes de material. Consulta do historico migrado do gestaocedec:
    // nao ha criacao pelo sistema novo, porque liberacao sem lancamento no
    // ledger seria registro sem lastro no estoque.
    //
    // A permissao e humanitaria.saldo.view, a mesma da consulta de estoque.
    // Liberacao e a saida do saldo, e o catalogo em config/permissions.php ja
    // agrupa saldo com a consulta de estoque. Um slug proprio exigiria decidir
    // quem o recebe; enquanto isso nao for definido, criar um deixaria a tela
    // visivel apenas para super-admin.
    Route::prefix('liberacoes')->name('liberacoes.')->group(function () {
        Route::get('/export', [LiberacaoAhController::class, 'export'])
            ->name('export')
            ->middleware('can:humanitaria.saldo.view');

        Route::get('/', [LiberacaoAhController::class, 'index'])
            ->name('index')
            ->middleware('can:humanitaria.saldo.view');

        Route::get('/{id}', [LiberacaoAhController::class, 'show'])
            ->name('show')
            ->middleware('can:humanitaria.saldo.view')
            ->whereNumber('id');
    });

    // Transferencias entre depositos. Consulta do historico migrado; a escrita,
    // quando existir, passa pelo ledger com um par TRANSF_SAIDA e TRANSF_ENTRADA.
    Route::prefix('transferencias')->name('transferencias.')->group(function () {
        Route::get('/export', [TransferenciaAhController::class, 'export'])
            ->name('export')
            ->middleware('can:humanitaria.saldo.view');

        Route::get('/', [TransferenciaAhController::class, 'index'])
            ->name('index')
            ->middleware('can:humanitaria.saldo.view');

        Route::get('/{id}', [TransferenciaAhController::class, 'show'])
            ->name('show')
            ->middleware('can:humanitaria.saldo.view')
            ->whereNumber('id');
    });

    // Entradas de material nos depositos.
    Route::prefix('entradas')->name('entradas.')->group(function () {
        Route::get('/export', [EntradaAhController::class, 'export'])
            ->name('export')
            ->middleware('can:humanitaria.saldo.view');

        Route::get('/', [EntradaAhController::class, 'index'])
            ->name('index')
            ->middleware('can:humanitaria.saldo.view');

        Route::get('/{id}', [EntradaAhController::class, 'show'])
            ->name('show')
            ->middleware('can:humanitaria.saldo.view')
            ->whereNumber('id');
    });

    Route::prefix('beneficiarios')->name('beneficiarios.')->group(function () {
        Route::get('/export', [BeneficiarioController::class, 'export'])
            ->name('export')
            ->middleware('can:humanitaria.beneficiarios.export');
        Route::get('/', [BeneficiarioController::class, 'index'])
            ->name('index')
            ->middleware('can:humanitaria.beneficiarios.view');
        Route::post('/', [BeneficiarioController::class, 'store'])
            ->name('store')
            ->middleware('can:humanitaria.beneficiarios.create');
        Route::get('/{id}', [BeneficiarioController::class, 'show'])
            ->name('show')
            ->middleware('can:humanitaria.beneficiarios.view');
        Route::put('/{id}', [BeneficiarioController::class, 'update'])
            ->name('update')
            ->middleware('can:humanitaria.beneficiarios.edit');
        Route::delete('/{id}', [BeneficiarioController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:humanitaria.beneficiarios.delete');
    });
});
