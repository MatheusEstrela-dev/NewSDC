<?php

use App\Modules\Estoque\Controllers\EstoqueController;
use Illuminate\Support\Facades\Route;

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
});

