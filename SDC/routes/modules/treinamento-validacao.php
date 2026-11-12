<?php

use App\Modules\Treinamento\Controllers\ValidarCertificadoController;
use Illuminate\Support\Facades\Route;

// Validacao publica de certificado: sem login e com throttle (spec 9.1).
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/validar-certificado/{codigo?}', [ValidarCertificadoController::class, 'mostrar'])
        ->name('certificados.validar');
    Route::post('/validar-certificado', [ValidarCertificadoController::class, 'buscar'])
        ->name('certificados.validar.buscar');
});
