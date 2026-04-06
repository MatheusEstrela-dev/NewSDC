<?php

use App\Modules\Pae\Controllers\PaeFormularioController;
use App\Modules\Pae\Controllers\PaeProtocoloController;

Route::prefix('pae')->name('pae.')->group(function () {

    // Lista de protocolos — pagina principal do modulo PAE
    Route::get('/', [PaeProtocoloController::class, 'index'])
        ->name('protocolos.index')
        ->middleware('can:pae.protocolos.view');

    // Formulario de gestao de PAE (criar / editar ficha do empreendimento)
    Route::get('/protocolo', [PaeFormularioController::class, 'show'])
        ->name('index')
        ->middleware('can:pae.empreendimentos.view');

    Route::post('/formulario', [PaeFormularioController::class, 'store'])
        ->name('formulario.store')
        ->middleware('can:pae.empreendimentos.create');

    Route::put('/formulario/{paeForm}/infogerais', [PaeFormularioController::class, 'updateInfoGerais'])
        ->name('formulario.infogerais')
        ->middleware('can:pae.empreendimentos.edit');

    Route::put('/formulario/{paeForm}/objetivo', [PaeFormularioController::class, 'updateObjetivoContexto'])
        ->name('formulario.objetivo')
        ->middleware('can:pae.empreendimentos.edit');

    Route::put('/formulario/{paeForm}/aptecnico', [PaeFormularioController::class, 'updateApontamentos'])
        ->name('formulario.aptecnico')
        ->middleware('can:pae.empreendimentos.edit');

    Route::put('/formulario/{paeForm}/conclusao', [PaeFormularioController::class, 'updateConclusao'])
        ->name('formulario.conclusao')
        ->middleware('can:pae.empreendimentos.edit');

    Route::put('/formulario/{paeForm}/finalizar', [PaeFormularioController::class, 'finalizar'])
        ->name('formulario.finalizar')
        ->middleware('can:pae.empreendimentos.edit');

    Route::post('/protocolo', [PaeProtocoloController::class, 'store'])
        ->name('protocolos.store')
        ->middleware('can:pae.protocolos.create');

    Route::post('/protocolo/{paeProtocolo}/status', [PaeProtocoloController::class, 'changeStatus'])
        ->name('protocolos.status')
        ->middleware('can:pae.protocolos.edit');

    Route::get('/export', [PaeProtocoloController::class, 'export'])
        ->name('export')
        ->middleware('can:pae.protocolos.export');
});
