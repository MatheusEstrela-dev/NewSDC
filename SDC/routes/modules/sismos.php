<?php

use App\Modules\Sismos\Controllers\SismosIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('sismos')->name('sismos.')->group(function () {
    Route::get('/', SismosIndexController::class)->name('index');
});
