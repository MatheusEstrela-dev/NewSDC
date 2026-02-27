<?php

use App\Modules\Suporte\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('suporte')->name('suporte.')->group(function () {
    Route::get('/', [SupportController::class, 'index'])->name('index');
    Route::post('/', [SupportController::class, 'store'])->name('store');
});
