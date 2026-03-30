<?php

use App\Models\Municipio;
use App\Modules\Pae\Controllers\PaeProtocoloController;
use Inertia\Inertia;

Route::prefix('pae')->name('pae.')->group(function () {

    Route::get('/', function () {
        return Inertia::render('Pae', [
            'municipios' => Municipio::orderBy('nome')->pluck('nome', 'id'),
        ]);
    })->name('index')
      ->middleware('can:pae.empreendimentos.view');

    Route::get('/protocolo', [PaeProtocoloController::class, 'index'])
        ->name('protocolos.index')
        ->middleware('can:pae.protocolos.view');

    Route::post('/protocolo', [PaeProtocoloController::class, 'store'])
        ->name('protocolos.store')
        ->middleware('can:pae.protocolos.create');

    Route::post('/protocolo/{protocolo}/status', [PaeProtocoloController::class, 'changeStatus'])
        ->name('protocolos.status')
        ->middleware('can:pae.protocolos.edit');

    Route::get('/export', [PaeProtocoloController::class, 'export'])
        ->name('export')
        ->middleware('can:pae.protocolos.export');
});
