<?php

use App\Http\Controllers\Api\V1\BI\EntradaController;
use Illuminate\Support\Facades\Route;

Route::prefix('bi')->name('api.v1.bi.')->group(function () {
    Route::apiResource('entrada', EntradaController::class)->only(['index', 'show']);
});


