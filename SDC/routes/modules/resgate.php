<?php

use App\Modules\Resgate\Controllers\CarteiraController;
use App\Modules\Resgate\Controllers\CatalogoController;
use Illuminate\Support\Facades\Route;

// Resgate de pontos - Fase 1: carteira somente leitura. Carregado por
// routes/modules/ranking.php, dentro do grupo autenticado.
Route::get('/ranking/resgate/carteira', [CarteiraController::class, 'show'])
    ->middleware('throttle:60,1')->name('resgate.carteira');

// Fase 2: catalogo versionado com quatro olhos. Escritas com throttle proprio.
Route::get('/ranking/resgate/catalogo', [CatalogoController::class, 'index'])
    ->middleware('throttle:60,1')->name('resgate.catalogo');
Route::get('/ranking/resgate/catalogo/propostas', [CatalogoController::class, 'propostas'])
    ->middleware('throttle:60,1')->name('resgate.catalogo.propostas');
Route::get('/ranking/resgate/catalogo/propostas/nova', [CatalogoController::class, 'novaProposta'])
    ->middleware('throttle:60,1')->name('resgate.catalogo.propostas.nova');
Route::get('/ranking/resgate/catalogo/unidades/nova', [CatalogoController::class, 'novaUnidade'])
    ->middleware('throttle:60,1')->name('resgate.catalogo.unidades.nova');
Route::post('/ranking/resgate/catalogo/propostas', [CatalogoController::class, 'propor'])
    ->middleware('throttle:20,1')->name('resgate.catalogo.propor');
Route::post('/ranking/resgate/catalogo/propostas/{proposta}/decisao', [CatalogoController::class, 'decidir'])
    ->whereNumber('proposta')->middleware('throttle:20,1')->name('resgate.catalogo.decidir');
Route::post('/ranking/resgate/catalogo/unidades', [CatalogoController::class, 'cadastrarUnidade'])
    ->middleware('throttle:20,1')->name('resgate.catalogo.unidades');
