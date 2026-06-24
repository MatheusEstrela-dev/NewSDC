<?php

use App\Modules\Pmda\Controllers\PmdaPlanoController;
use App\Modules\Pmda\Models\PmdaPlano;
use Illuminate\Support\Facades\Route;

Route::model('plano', PmdaPlano::class);

Route::prefix('pmda')->name('pmda.')->group(function () {
    Route::prefix('planos')->name('planos.')->group(function () {
        Route::get('/', [PmdaPlanoController::class, 'index'])
            ->name('index')->middleware('can:pmda.planos.view');
        Route::post('/', [PmdaPlanoController::class, 'store'])
            ->name('store')->middleware('can:pmda.planos.create');
        Route::get('/{plano}/edit', [PmdaPlanoController::class, 'edit'])
            ->name('edit')->middleware('can:pmda.planos.edit');
        Route::put('/{plano}', [PmdaPlanoController::class, 'update'])
            ->name('update')->middleware('can:pmda.planos.edit');
        Route::post('/{plano}/copiar', [PmdaPlanoController::class, 'copiar'])
            ->name('copiar')->middleware('can:pmda.planos.copiar');
    });
});
