<?php

use App\Modules\Plantao\Presentation\Http\Controllers\PlantaoIndexController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Plantão
|--------------------------------------------------------------------------
*/

Route::prefix('plantao')->name('plantao.')->group(function () {

    Route::get('/export', \App\Modules\Plantao\Presentation\Http\Controllers\PlantaoExportController::class)
        ->name('export')
        ->middleware('can:plantao.turnos.export');

    Route::get('/', PlantaoIndexController::class)
        ->name('index')
        ->middleware('can:plantao.turnos.view');
});
