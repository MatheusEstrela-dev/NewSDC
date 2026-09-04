<?php

use App\Modules\Geoespacial\Controllers\GeoUploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('geoespacial')->name('geoespacial.')->group(function () {
    Route::get('/', [GeoUploadController::class, 'index'])->name('index');
    Route::post('/', [GeoUploadController::class, 'upload'])->name('upload');

    // Fila de revisao da CEDEC. Verbo POST nas decisoes porque mudam estado;
    // GET aprovaria camada por prefetch do navegador.
    Route::get('/revisao', [GeoUploadController::class, 'revisao'])->name('revisao');
    Route::post('/revisao/{camada}/aprovar', [GeoUploadController::class, 'aprovar'])->name('aprovar');
    Route::post('/revisao/{camada}/recusar', [GeoUploadController::class, 'recusar'])->name('recusar');
});
