<?php

use App\Modules\Inventario\Controllers\InventarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventario')->name('inventario.')->group(function () {
    Route::get('/', [InventarioController::class, 'index'])
        ->name('index')
        ->middleware('can:inventario.equipamentos.view');
});
