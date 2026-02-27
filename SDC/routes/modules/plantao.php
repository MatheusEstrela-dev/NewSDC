<?php

use App\Modules\Plantao\Controllers\PlantaoExportController;
use App\Modules\Plantao\Controllers\PlantaoIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('plantao')->name('plantao.')->group(function () {

    Route::get('/export', PlantaoExportController::class)
        ->name('export')
        ->middleware('can:plantao.turnos.export');

    Route::get('/', PlantaoIndexController::class)
        ->name('index')
        ->middleware('can:plantao.turnos.view');
});
