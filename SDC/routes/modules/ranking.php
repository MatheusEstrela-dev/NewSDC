<?php

use App\Modules\Ranking\Controllers\RankingController;
use Illuminate\Support\Facades\Route;

Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
Route::patch('/ranking/regras/{ruleKey}', [RankingController::class, 'atualizarRegra'])
    ->where('ruleKey', '[a-z0-9._-]+')
    ->middleware('throttle:30,1')->name('ranking.regras.atualizar');
Route::post('/ranking/simular', \App\Modules\Ranking\Controllers\RankingSimulationController::class)
    ->middleware('throttle:60,1')->name('ranking.simular');
