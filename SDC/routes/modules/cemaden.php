<?php

use App\Modules\Cemaden\Controllers\CemadenIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('cemaden')->name('cemaden.')->group(function () {
    Route::get('/', CemadenIndexController::class)->name('index');
});
