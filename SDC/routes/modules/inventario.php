<?php

use App\Modules\Inventario\Controllers\InventarioController;
use App\Modules\Inventario\Controllers\EquipamentoController;
use App\Modules\Inventario\Controllers\EstacaoController;
use App\Modules\Inventario\Controllers\MovimentacaoController;
use App\Modules\Inventario\Controllers\RemanejamentoController;
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
        ->name('movimentacoes.devolver')->middleware('can:inventario.emprestimos.return')->whereNumber('movimentacao');

    // Lotes de remanejamento. {remanejamento} e uuid: whereUuid devolve 404 (e
    // nao 500 do cast do PostgreSQL) para id malformado. O nome nao tem
    // Route::model() global (conferido com grep em routes/ e app/Providers).
    Route::get('/remanejamentos/novo', [RemanejamentoController::class, 'create'])
        ->name('remanejamentos.create')->middleware('can:inventario.remanejamentos.create');
    Route::post('/remanejamentos', [RemanejamentoController::class, 'store'])
        ->name('remanejamentos.store')->middleware('can:inventario.remanejamentos.create');
    Route::get('/remanejamentos/{remanejamento}/editar', [RemanejamentoController::class, 'edit'])
        ->name('remanejamentos.edit')->middleware('can:inventario.remanejamentos.edit')->whereUuid('remanejamento');
    Route::put('/remanejamentos/{remanejamento}', [RemanejamentoController::class, 'update'])
        ->name('remanejamentos.update')->middleware('can:inventario.remanejamentos.edit')->whereUuid('remanejamento');
    Route::post('/remanejamentos/{remanejamento}/desfazer', [RemanejamentoController::class, 'desfazer'])
        ->name('remanejamentos.desfazer')->middleware('can:inventario.remanejamentos.edit')->whereUuid('remanejamento');
    Route::get('/remanejamentos/{remanejamento}/planilha', [RemanejamentoController::class, 'planilha'])
        ->name('remanejamentos.planilha')->middleware('can:inventario.emprestimos.export')->whereUuid('remanejamento');
    Route::post('/remanejamentos/{remanejamento}/chamado', [RemanejamentoController::class, 'chamado'])
        ->name('remanejamentos.chamado')->middleware('can:inventario.remanejamentos.edit')->whereUuid('remanejamento');
    Route::post('/remanejamentos/{remanejamento}/seplag', [RemanejamentoController::class, 'seplag'])
        ->name('remanejamentos.seplag')->middleware(['can:inventario.remanejamentos.seplag', 'throttle:inventario-seplag'])->whereUuid('remanejamento');
});
