<?php

use App\Modules\Resgate\Controllers\CarteiraController;
use App\Modules\Resgate\Controllers\CatalogoController;
use App\Modules\Resgate\Controllers\ExecucaoController;
use App\Modules\Resgate\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

// Resgate de pontos - Fase 1: carteira somente leitura. Carregado por
// routes/modules/ranking.php, dentro do grupo autenticado.
Route::get('/ranking/resgate/carteira', [CarteiraController::class, 'show'])
    ->middleware('throttle:60,1')->name('resgate.carteira');

// Fase 2: catalogo versionado com quatro olhos. Escritas com throttle proprio.
Route::get('/ranking/resgate/catalogo', [CatalogoController::class, 'index'])
    ->middleware('throttle:60,1')->name('resgate.catalogo');
Route::get('/ranking/resgate/catalogo/propostas', [CatalogoController::class, 'propostas'])
    ->middleware('throttle:60,1')->name('resgate.catalogo.propostas');
Route::get('/ranking/resgate/catalogo/propostas/nova', [CatalogoController::class, 'novaProposta'])
    ->middleware('throttle:60,1')->name('resgate.catalogo.propostas.nova');
Route::get('/ranking/resgate/catalogo/unidades/nova', [CatalogoController::class, 'novaUnidade'])
    ->middleware('throttle:60,1')->name('resgate.catalogo.unidades.nova');
Route::post('/ranking/resgate/catalogo/propostas', [CatalogoController::class, 'propor'])
    ->middleware('throttle:20,1')->name('resgate.catalogo.propor');
Route::post('/ranking/resgate/catalogo/propostas/{proposta}/decisao', [CatalogoController::class, 'decidir'])
    ->whereNumber('proposta')->middleware('throttle:20,1')->name('resgate.catalogo.decidir');
Route::post('/ranking/resgate/catalogo/unidades', [CatalogoController::class, 'cadastrarUnidade'])
    ->middleware('throttle:20,1')->name('resgate.catalogo.unidades');

// Fase 3: pedido de resgate. Nasce RESERVADO e aguarda a decisao da CEDEC.
Route::get('/ranking/resgate/pedidos', [PedidoController::class, 'index'])
    ->middleware('throttle:60,1')->name('resgate.pedidos');
Route::get('/ranking/resgate/pedidos/novo', [PedidoController::class, 'novo'])
    ->middleware('throttle:60,1')->name('resgate.pedidos.novo');
Route::post('/ranking/resgate/pedidos', [PedidoController::class, 'solicitar'])
    ->middleware('throttle:10,1')->name('resgate.pedidos.solicitar');
Route::get('/ranking/resgate/pedidos/{pedido}', [PedidoController::class, 'show'])
    ->whereNumber('pedido')->middleware('throttle:60,1')->name('resgate.pedidos.show');
Route::post('/ranking/resgate/pedidos/{pedido}/decisao', [PedidoController::class, 'decidir'])
    ->whereNumber('pedido')->middleware('throttle:20,1')->name('resgate.pedidos.decidir');
Route::post('/ranking/resgate/pedidos/{pedido}/cancelamento', [PedidoController::class, 'cancelar'])
    ->whereNumber('pedido')->middleware('throttle:20,1')->name('resgate.pedidos.cancelar');

// Fase 4: execucao do pedido aprovado - termo/SEI, assinaturas, entrega,
// confirmacao ou contestacao pelo municipio, e anexos com hash conferido.
Route::prefix('/ranking/resgate/pedidos/{pedido}')->whereNumber('pedido')->group(function (): void {
    Route::post('/termo', [ExecucaoController::class, 'termo'])->middleware('throttle:10,1')->name('resgate.pedidos.termo');
    Route::post('/assinatura', [ExecucaoController::class, 'assinar'])->middleware('throttle:10,1')->name('resgate.pedidos.assinar');
    Route::post('/entrega', [ExecucaoController::class, 'entregar'])->middleware('throttle:10,1')->name('resgate.pedidos.entregar');
    Route::post('/confirmacao', [ExecucaoController::class, 'confirmar'])->middleware('throttle:10,1')->name('resgate.pedidos.confirmar');
    Route::post('/contestacao', [ExecucaoController::class, 'contestar'])->middleware('throttle:10,1')->name('resgate.pedidos.contestar');
    Route::get('/documentos/{documento}', [ExecucaoController::class, 'documento'])->whereNumber('documento')
        ->middleware('throttle:60,1')->name('resgate.pedidos.documento');
});
