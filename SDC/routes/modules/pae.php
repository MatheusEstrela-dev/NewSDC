<?php

use App\Modules\Pae\Controllers\PaeProtocoloController;
use Inertia\Inertia;

Route::prefix('pae')->name('pae.')->group(function () {

    Route::get('/export', [PaeProtocoloController::class, 'export'])
        ->name('export')
        ->middleware('can:pae.protocolos.export');

    Route::get('/', function () {
        return Inertia::render('Pae');
    })->name('index')
      ->middleware('can:pae.empreendimentos.view');

    Route::get('/protocolo', function () {
        return Inertia::render('PaeProtocolosIndex');
    })->name('protocolos.index')
      ->middleware('can:pae.protocolos.view');

});

