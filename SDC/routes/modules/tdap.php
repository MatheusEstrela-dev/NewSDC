<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Tdap\Presentation\Http\Controllers\TdapDashboardController;
use App\Modules\Tdap\Presentation\Http\Controllers\TdapProductsController;
use App\Modules\Tdap\Presentation\Http\Controllers\TdapRecebimentosController;
use App\Modules\Tdap\Presentation\Http\Controllers\TdapMovimentacoesController;

/*
|--------------------------------------------------------------------------
| Rotas do Módulo TDAP (Gestão de Depósito)
|--------------------------------------------------------------------------
|
| Este arquivo contém as rotas para o módulo de Gestão de Depósito (TDAP)
| que gerencia produtos, recebimentos, movimentações e estoque.
|
*/

Route::middleware(['auth', 'verified'])
    ->prefix('tdap')
    ->name('tdap.')
    ->group(function () {

        // Dashboard
        Route::get('/', [TdapDashboardController::class, 'index'])
            ->name('dashboard');

        // Produtos
        Route::prefix('produtos')
            ->name('products.')
            ->group(function () {
                Route::get('/', [TdapProductsController::class, 'index'])
                    ->name('index');

                Route::post('/', [TdapProductsController::class, 'store'])
                    ->name('store')
                    ->middleware('can:tdap.products.create');

                Route::get('/{product}/estoque', [TdapProductsController::class, 'estoque'])
                    ->name('estoque');
            });

        // Recebimentos (Modal TDPA)
        Route::prefix('recebimentos')
            ->name('recebimentos.')
            ->group(function () {
                Route::get('/', [TdapRecebimentosController::class, 'index'])
                    ->name('index');

                Route::post('/', [TdapRecebimentosController::class, 'store'])
                    ->name('store')
                    ->middleware('can:tdap.recebimentos.create');

                Route::get('/{recebimento}', [TdapRecebimentosController::class, 'show'])
                    ->name('show');

                Route::post('/{recebimento}/processar', [TdapRecebimentosController::class, 'processar'])
                    ->name('processar')
                    ->middleware('can:tdap.recebimentos.processar');
            });

        // Movimentações
        Route::prefix('movimentacoes')
            ->name('movimentacoes.')
            ->group(function () {
                Route::get('/', [TdapMovimentacoesController::class, 'index'])
                    ->name('index');

                Route::post('/saida', [TdapMovimentacoesController::class, 'saida'])
                    ->name('saida')
                    ->middleware('can:tdap.movimentacoes.create');

                Route::get('/produto/{product}/historico', [TdapMovimentacoesController::class, 'historico'])
                    ->name('historico');
            });
    });

// Rotas de administração do TDAP
Route::middleware(['auth', 'verified', 'can:tdap.admin'])
    ->prefix('admin/tdap')
    ->name('admin.tdap.')
    ->group(function () {

        Route::get('/', [TdapDashboardController::class, 'index'])
            ->name('dashboard');

        // Aqui podem entrar configurações, relatórios, etc.
    });
