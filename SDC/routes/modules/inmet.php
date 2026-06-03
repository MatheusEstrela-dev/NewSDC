<?php

use App\Modules\Inmet\Controllers\InmetIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('inmet')->name('inmet.')->group(function () {
    Route::get('/', InmetIndexController::class)->name('index');
});
