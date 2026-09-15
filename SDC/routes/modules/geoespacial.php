<?php

use App\Modules\Geoespacial\Controllers\GeoCamadaController;
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

    /*
     * Ciclo de vida de UMA camada.
     *
     * Declaradas DEPOIS de /enviar e /revisao, e com whereNumber: sem as duas
     * coisas, `/geoespacial/enviar` casaria com `/geoespacial/{camada}` e a tela
     * de envio viraria "camada 'enviar' nao encontrada". A ordem de registro
     * decide, no Laravel, qual rota responde.
     *
     * O middleware `can:` cobre a CAPACIDADE. A regra de instancia -- e minha?
     * em que estado esta? -- fica em Support\AcessoACamada, chamada pelo
     * controller e pelo authorize() do Request, porque middleware nao conhece a
     * camada.
     */
    Route::get('/{camada}', [GeoCamadaController::class, 'show'])
        ->middleware('can:geoespacial.camadas.view')
        ->whereNumber('camada')
        ->name('show');

    Route::put('/{camada}', [GeoCamadaController::class, 'update'])
        ->middleware('can:geoespacial.camadas.edit')
        ->whereNumber('camada')
        ->name('update');

    Route::get('/{camada}/arquivo', [GeoCamadaController::class, 'baixar'])
        ->middleware('can:geoespacial.camadas.export')
        ->whereNumber('camada')
        ->name('arquivo');

    // Arquivar, reativar e reprocessar mudam o mapa do estado: POST, nunca GET.
    Route::post('/{camada}/arquivar', [GeoCamadaController::class, 'arquivar'])
        ->middleware('can:geoespacial.camadas.arquivar')
        ->whereNumber('camada')
        ->name('arquivar');

    Route::post('/{camada}/reativar', [GeoCamadaController::class, 'reativar'])
        ->middleware('can:geoespacial.camadas.arquivar')
        ->whereNumber('camada')
        ->name('reativar');

    Route::post('/{camada}/reprocessar', [GeoCamadaController::class, 'reprocessar'])
        ->middleware('can:geoespacial.camadas.revisar')
        ->whereNumber('camada')
        ->name('reprocessar');
});
