<?php

use App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios\BeneficiarioIndexController;
use App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios\BeneficiarioShowController;
use App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios\BeneficiarioStoreController;
use App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios\BeneficiarioUpdateController;
use App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios\BeneficiarioDestroyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Ajuda Humanitária
|--------------------------------------------------------------------------
*/

// Ja esta dentro do middleware auth do web.php, entao nao precisa redefinir
Route::prefix('ajuda-humanitaria')->name('ajuda-humanitaria.')->group(function () {

    // Beneficiarios
    Route::prefix('beneficiarios')->name('beneficiarios.')->group(function () {
        Route::get('/export', \App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios\BeneficiarioExportController::class)
            ->name('export')
            ->middleware('can:humanitaria.beneficiarios.export');
        Route::get('/', BeneficiarioIndexController::class)
            ->name('index')
            ->middleware('can:humanitaria.beneficiarios.view');
        Route::post('/', BeneficiarioStoreController::class)
            ->name('store')
            ->middleware('can:humanitaria.beneficiarios.create');
        Route::get('/{id}', BeneficiarioShowController::class)
            ->name('show')
            ->middleware('can:humanitaria.beneficiarios.view');
        Route::put('/{id}', BeneficiarioUpdateController::class)
            ->name('update')
            ->middleware('can:humanitaria.beneficiarios.edit');
        Route::delete('/{id}', BeneficiarioDestroyController::class)
            ->name('destroy')
            ->middleware('can:humanitaria.beneficiarios.delete');
    });

    // TODO: Adicionar rotas de outros recursos
    // Abrigos
    // Route::prefix('abrigos')->name('abrigos.')->group(function () {
    //     Route::get('/', AbrigoIndexController::class)->name('index');
    // });

    // Doações
    // Route::prefix('doacoes')->name('doacoes.')->group(function () {
    //     Route::get('/', DoacaoIndexController::class)->name('index');
    // });

    // Auxílios
    // Route::prefix('auxilios')->name('auxilios.')->group(function () {
    //     Route::get('/', AuxilioIndexController::class)->name('index');
    // });

    // Estoque
    // Route::prefix('estoque')->name('estoque.')->group(function () {
    //     Route::get('/', EstoqueIndexController::class)->name('index');
    // });
});
