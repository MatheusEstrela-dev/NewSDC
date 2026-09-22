<?php

use App\Modules\Ranking\Controllers\RankingController;
use Illuminate\Support\Facades\Route;

Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
Route::post('/ranking/simular', \App\Modules\Ranking\Controllers\RankingSimulationController::class)
    ->middleware('throttle:60,1')->name('ranking.simular');
