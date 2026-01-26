<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Rat\Presentation\Http\Controllers\RatIndexController;

Route::prefix('rat')->name('rat.')->group(function () {
    // Rota para retornar dados JSON de um RAT específico (usado pelo modal de impressão)
    Route::get('/{id}/json', [RatIndexController::class, 'showJson'])->name('show.json');
});

