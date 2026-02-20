<?php

use App\Modules\PlanCon\Presentation\Http\Controllers\MunicipiosComPlanoController;
use App\Modules\PlanCon\Presentation\Http\Controllers\MunicipiosSemPlanoController;
use App\Modules\PlanCon\Presentation\Http\Controllers\PlanConIndexController;
use App\Modules\PlanCon\Presentation\Http\Controllers\PlanConStatsController;
use Illuminate\Support\Facades\Route;

Route::prefix('plancon')->name('plancon.')->group(function () {
    Route::get('/', PlanConIndexController::class)->name('index');
    Route::get('/stats', PlanConStatsController::class)->name('stats');
    Route::get('/municipios/com-plano', MunicipiosComPlanoController::class)->name('municipios.com');
    Route::get('/municipios/sem-plano', MunicipiosSemPlanoController::class)->name('municipios.sem');
});
