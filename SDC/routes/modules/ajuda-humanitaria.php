<?php

use App\Modules\AjudaHumanitaria\Controllers\BeneficiarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('ajuda-humanitaria')->name('ajuda-humanitaria.')->group(function () {

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
