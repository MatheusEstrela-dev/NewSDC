<?php

use App\Modules\Estoque\Controllers\EstoqueController;
use Illuminate\Support\Facades\Route;

/*
| Modulo /estoque (#32 inventario de donativos, #33 estoque e kits). A API
| equivalente esta em routes/api.php: /api/v1/inventario e /api/v1/estoque.
*/
Route::prefix('estoque')->name('estoque.')->group(function () {
    Route::get('/', [EstoqueController::class, 'index'])
        ->defaults('section', 'dashboard')
        ->name('index')
        ->middleware('can:estoque.produtos.view');

    Route::get('/produtos', [EstoqueController::class, 'index'])
        ->defaults('section', 'produtos')
        ->name('produtos.index')
        ->middleware('can:estoque.produtos.view');

    Route::get('/kits', [EstoqueController::class, 'index'])
        ->defaults('section', 'kits')
        ->name('kits.index')
        ->middleware('can:estoque.kits.view');

    Route::get('/movimentacoes', [EstoqueController::class, 'index'])
        ->defaults('section', 'movimentacoes')
        ->name('movimentacoes.index')
        ->middleware('can:estoque.movimentacoes.view');

    Route::get('/export-excel', [EstoqueController::class, 'exportExcel'])
        ->name('export-excel')
        ->middleware('can:estoque.produtos.export');

    // Proxima pagina da lista (scroll infinito).
    Route::get('/lotes', [EstoqueController::class, 'lotes'])
        ->name('lotes.lista')
        ->middleware('can:estoque.produtos.view');

    Route::post('/produtos', [EstoqueController::class, 'storeProduto'])
        ->name('produtos.store')
        ->middleware('can:estoque.produtos.create');
    Route::put('/produtos/{produto}', [EstoqueController::class, 'updateProduto'])
        ->name('produtos.update')
        ->whereNumber('produto')
        ->middleware('can:estoque.produtos.edit');
    Route::delete('/produtos/{produto}', [EstoqueController::class, 'destroyProduto'])
        ->name('produtos.destroy')
        ->whereNumber('produto')
        ->middleware('can:estoque.produtos.delete');

    Route::post('/entradas', [EstoqueController::class, 'entrada'])
        ->name('entradas.store')
        ->middleware('can:estoque.movimentacoes.create');
    Route::post('/saidas', [EstoqueController::class, 'saida'])
        ->name('saidas.store')
        ->middleware('can:estoque.movimentacoes.create');
    Route::get('/romaneios/{romaneio}', [EstoqueController::class, 'romaneio'])
        ->name('romaneios.show')
        ->where('romaneio', 'ROM-[0-9]{4}-[0-9]{5}')
        ->middleware('can:estoque.movimentacoes.view');

    Route::post('/almoxarifados', [EstoqueController::class, 'storeAlmoxarifado'])
        ->name('almoxarifados.store')
        ->middleware('can:estoque.produtos.create');
    Route::post('/kits', [EstoqueController::class, 'storeKit'])
        ->name('kits.store')
        ->middleware('can:estoque.kits.create');
    Route::patch('/lotes/{lote}/quarentena', [EstoqueController::class, 'quarentena'])
        ->name('lotes.quarentena')
        ->whereNumber('lote')
        ->middleware('can:estoque.lotes.edit');
});
