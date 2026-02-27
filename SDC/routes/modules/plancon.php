<?php

use App\Modules\PlanCon\Controllers\PlanConController;
use Illuminate\Support\Facades\Route;

Route::prefix('plancon')->name('plancon.')->group(function () {
    Route::get('/', [PlanConController::class, 'index'])->name('index');
    Route::get('/stats', [PlanConController::class, 'stats'])->name('stats');
    Route::get('/municipios/com-plano', [PlanConController::class, 'municipiosComPlano'])->name('municipios.com');
    Route::get('/municipios/sem-plano', [PlanConController::class, 'municipiosSemPlano'])->name('municipios.sem');
});
