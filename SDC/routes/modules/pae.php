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

    Route::get('/protocolo/{paeProtocolo}/show', [PaeFormularioController::class, 'showProtocolo'])
        ->name('protocolo.show')
        ->middleware('can:pae.empreendimentos.view');

    Route::get('/protocolo/{paeProtocolo}/edit', [PaeFormularioController::class, 'edit'])
        ->name('protocolo.edit')
        ->middleware('can:pae.empreendimentos.edit');

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

    Route::post('/formulario/{paeForm}/anexos', [PaeFormularioController::class, 'storeAnexo'])
        ->name('formulario.anexos.store')
        ->middleware('can:pae.empreendimentos.edit');

    Route::delete('/formulario/{paeForm}/anexos/{paeFormAnexo}', [PaeFormularioController::class, 'destroyAnexo'])
        ->name('formulario.anexos.destroy')
        ->middleware('can:pae.empreendimentos.edit');

    Route::get('/formulario/{paeForm}/anexos/{paeFormAnexo}/download', [PaeFormularioController::class, 'downloadAnexo'])
        ->name('formulario.anexos.download')
        ->middleware('can:pae.empreendimentos.view');

    Route::put('/formulario/{paeForm}/finalizar', [PaeFormularioController::class, 'finalizar'])
        ->name('formulario.finalizar')
        ->middleware('can:pae.empreendimentos.edit');

    Route::get('/protocolo/proximo-numero', [PaeProtocoloController::class, 'proximoNumero'])
        ->name('protocolos.proximo-numero')
        ->middleware('can:pae.protocolos.create');

    Route::post('/protocolo', [PaeProtocoloController::class, 'store'])
        ->name('protocolos.store')
        ->middleware('can:pae.protocolos.create');

    Route::get('/protocolo/{paeProtocolo}/historico', [PaeProtocoloController::class, 'historico'])
        ->name('protocolos.historico')
        ->middleware('can:pae.protocolos.view');

    Route::post('/protocolo/{paeProtocolo}/status', [PaeProtocoloController::class, 'changeStatus'])
        ->name('protocolos.status')
        ->middleware('can:pae.protocolos.edit');

    Route::put('/protocolo/{paeProtocolo}/assign', [PaeProtocoloController::class, 'assign'])
        ->name('protocolo.assign')
        ->middleware('can:pae.protocolos.atribuir');

    Route::delete('/protocolos/{paeProtocolo}', [PaeProtocoloController::class, 'destroy'])
        ->name('protocolos.destroy')
        ->middleware('can:pae.protocolos.delete');

    Route::get('/export', [PaeProtocoloController::class, 'export'])
        ->name('export')
        ->middleware('can:pae.protocolos.export');
});
