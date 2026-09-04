<?php

use App\Modules\Geoespacial\Controllers\GeoUploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('geoespacial')->name('geoespacial.')->group(function () {
    Route::get('/', [GeoUploadController::class, 'index'])->name('index');
    Route::post('/', [GeoUploadController::class, 'upload'])->name('upload');
});
