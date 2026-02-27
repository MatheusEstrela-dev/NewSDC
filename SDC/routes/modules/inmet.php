<?php

use App\Modules\Inmet\Controllers\InmetIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('inmet')->name('inmet.')->group(function () {
    Route::get('/', InmetIndexController::class)->name('index');
});
