<?php

use App\Modules\Inmet\Presentation\Http\Controllers\InmetIndexController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo INMET (Meteorologia)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->prefix('inmet')->name('inmet.')->group(function () {
    // Index - Mapa de estações meteorológicas
    Route::get('/', InmetIndexController::class)->name('index');
});
