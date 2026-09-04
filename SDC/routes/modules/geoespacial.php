<?php

use App\Modules\Geoespacial\Controllers\GeoUploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('geoespacial')->name('geoespacial.')->group(function () {
    // As permissoes eram declaradas em config e nao exigidas em lugar nenhum:
    // qualquer autenticado lia e enviava. O can: fecha isso na porta, antes de
    // o controller abrir arquivo de terceiro.
    Route::get('/', [GeoUploadController::class, 'index'])
        ->middleware('can:geoespacial.camadas.view')
        ->name('index');

    // Tela propria de envio, com o processo explicado. Exige a mesma permissao
    // do POST: nao adianta mostrar o formulario para quem nao pode enviar.
    Route::get('/enviar', [GeoUploadController::class, 'enviar'])
        ->middleware('can:geoespacial.camadas.enviar')
        ->name('enviar');

    Route::post('/', [GeoUploadController::class, 'upload'])
        ->middleware('can:geoespacial.camadas.enviar')
        ->name('upload');

    // Fila de revisao da CEDEC. Verbo POST nas decisoes porque mudam estado;
    // GET aprovaria camada por prefetch do navegador.
    Route::get('/revisao', [GeoUploadController::class, 'revisao'])->name('revisao');
    Route::post('/revisao/{camada}/aprovar', [GeoUploadController::class, 'aprovar'])->name('aprovar');
    Route::post('/revisao/{camada}/recusar', [GeoUploadController::class, 'recusar'])->name('recusar');
});
