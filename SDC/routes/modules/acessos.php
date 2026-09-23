<?php

use App\Modules\Acessos\Controllers\CadastroAcessoController;
use Illuminate\Support\Facades\Route;

Route::prefix('acessos')->name('acessos.')->group(function (): void {
    Route::get('/', [CadastroAcessoController::class, 'index'])->name('index')->middleware('can:acessos.cadastros.view');
    Route::post('/', [CadastroAcessoController::class, 'store'])->name('store')->middleware('can:acessos.cadastros.create');
    Route::get('/{cadastro}', [CadastroAcessoController::class, 'show'])->name('show')->middleware('can:acessos.cadastros.view');
    Route::put('/{cadastro}', [CadastroAcessoController::class, 'update'])->name('update')->middleware('can:acessos.cadastros.edit');
    Route::post('/{cadastro}/aprovar', [CadastroAcessoController::class, 'aprovar'])->name('aprovar')->middleware('can:acessos.cadastros.approve');
    Route::post('/{cadastro}/status', [CadastroAcessoController::class, 'alterarStatus'])->name('status')->middleware('can:acessos.cadastros.approve');
});
