<?php

use App\Modules\AjudaHumanitaria\Controllers\BeneficiarioController;
use App\Modules\AjudaHumanitaria\Controllers\PedidoAhController;
use App\Modules\AjudaHumanitaria\Controllers\PedidoAhDashboardController;
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
