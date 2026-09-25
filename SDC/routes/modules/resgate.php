<?php

use App\Modules\Resgate\Controllers\CarteiraController;
use Illuminate\Support\Facades\Route;

// Resgate de pontos - Fase 1: carteira somente leitura. Carregado por
// routes/modules/ranking.php, dentro do grupo autenticado.
Route::get('/ranking/resgate/carteira', [CarteiraController::class, 'show'])
    ->middleware('throttle:60,1')->name('resgate.carteira');
