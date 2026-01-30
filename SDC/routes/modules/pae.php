<?php

use App\Modules\Pae\Presentation\Http\Controllers\PaeProtocoloController;

Route::prefix('pae')->name('pae.')->group(function () {

    Route::get('/export', [PaeProtocoloController::class, 'export'])->name('export');

    Route::get('/', function () {
        return Inertia::render('Pae');
    })->name('index');

    Route::get('/protocolo', function () {
        return Inertia::render('PaeProtocolosIndex');
    })->name('protocolos.index');

});

