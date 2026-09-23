<?php

use App\Modules\Inventario\Controllers\InventarioController;
use App\Modules\Inventario\Controllers\EquipamentoController;
use App\Modules\Inventario\Controllers\EstacaoController;
use App\Modules\Inventario\Controllers\MovimentacaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventario')->name('inventario.')->group(function () {
    Route::get('/', [InventarioController::class, 'index'])
        ->name('index')
        ->middleware('can:inventario.equipamentos.view');

    Route::post('/equipamentos', [EquipamentoController::class, 'store'])
        ->name('equipamentos.store')->middleware('can:inventario.equipamentos.create');
    Route::put('/equipamentos/{equipamento}', [EquipamentoController::class, 'update'])
        ->name('equipamentos.update')->middleware('can:inventario.equipamentos.edit');
    Route::delete('/equipamentos/{equipamento}', [EquipamentoController::class, 'destroy'])
        ->name('equipamentos.destroy')->middleware('can:inventario.equipamentos.delete');

    Route::get('/estacoes', [EstacaoController::class, 'index'])
        ->name('estacoes.index')->middleware('can:inventario.equipamentos.view');
    Route::post('/estacoes', [EstacaoController::class, 'store'])
        ->name('estacoes.store')->middleware('can:inventario.equipamentos.create');
    Route::put('/estacoes/{estacao}', [EstacaoController::class, 'update'])
        ->name('estacoes.update')->middleware('can:inventario.equipamentos.edit');
    Route::delete('/estacoes/{estacao}', [EstacaoController::class, 'destroy'])
        ->name('estacoes.destroy')->middleware('can:inventario.equipamentos.delete');

    Route::get('/movimentacoes', [MovimentacaoController::class, 'index'])
        ->name('movimentacoes.index')->middleware('can:inventario.emprestimos.view');
    Route::post('/movimentacoes', [MovimentacaoController::class, 'store'])
        ->name('movimentacoes.store')->middleware('can:inventario.emprestimos.create');
    Route::post('/movimentacoes/{movimentacao}/devolver', [MovimentacaoController::class, 'devolver'])
        ->name('movimentacoes.devolver')->middleware('can:inventario.emprestimos.return');
});
