<?php

declare(strict_types=1);

use App\Modules\Tdap\Controllers\CaminhaoController;
use App\Modules\Tdap\Controllers\PrestadorController;
use App\Modules\Tdap\Controllers\TdapDashboardController;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Prestador;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TDAP - Transporte e Distribuicao de Agua Potavel
|--------------------------------------------------------------------------
|
| Gestao de prestadores, caminhoes-tanque, atas, lotes, cronogramas de
| fornecimento, viagens, vistorias e historico de auditoria.
|
| Carregado por routes/web.php dentro do middleware 'auth'.
|
| Plano: docs/superpowers/plans/2026-05-11-tdap-migration.md
*/

Route::model('prestador', Prestador::class);
Route::model('caminhao', Caminhao::class);

Route::prefix('tdap')->name('tdap.')->group(function () {

    Route::get('/', [TdapDashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('can:tdap.dashboard.view');

    /* Prestadores (Fase 1) */
    Route::prefix('prestadores')->name('prestadores.')->group(function () {
        Route::middleware('can:tdap.prestadores.view')->group(function () {
            Route::get('/', [PrestadorController::class, 'index'])->name('index');
            Route::get('/{prestador}', [PrestadorController::class, 'show'])
                ->name('show')->whereNumber('prestador');
        });

        Route::middleware('can:tdap.prestadores.create')->group(function () {
            Route::get('/novo/cadastrar', [PrestadorController::class, 'create'])->name('create');
            Route::post('/', [PrestadorController::class, 'store'])->name('store');
        });

        Route::middleware('can:tdap.prestadores.edit')->group(function () {
            Route::get('/{prestador}/editar', [PrestadorController::class, 'edit'])
                ->name('edit')->whereNumber('prestador');
            Route::put('/{prestador}', [PrestadorController::class, 'update'])
                ->name('update')->whereNumber('prestador');
        });

        Route::middleware('can:tdap.prestadores.delete')->group(function () {
            Route::delete('/{prestador}', [PrestadorController::class, 'destroy'])
                ->name('destroy')->whereNumber('prestador');
        });
    });

    /* Caminhoes (Fase 1) */
    Route::prefix('caminhoes')->name('caminhoes.')->group(function () {
        Route::middleware('can:tdap.caminhoes.view')->group(function () {
            Route::get('/', [CaminhaoController::class, 'index'])->name('index');
            Route::get('/{caminhao}', [CaminhaoController::class, 'show'])
                ->name('show')->whereNumber('caminhao');
        });

        Route::middleware('can:tdap.caminhoes.create')->group(function () {
            Route::get('/novo/cadastrar', [CaminhaoController::class, 'create'])->name('create');
            Route::post('/', [CaminhaoController::class, 'store'])->name('store');
        });

        Route::middleware('can:tdap.caminhoes.edit')->group(function () {
            Route::get('/{caminhao}/editar', [CaminhaoController::class, 'edit'])
                ->name('edit')->whereNumber('caminhao');
            Route::put('/{caminhao}', [CaminhaoController::class, 'update'])
                ->name('update')->whereNumber('caminhao');
        });

        Route::middleware('can:tdap.caminhoes.delete')->group(function () {
            Route::delete('/{caminhao}', [CaminhaoController::class, 'destroy'])
                ->name('destroy')->whereNumber('caminhao');
        });
    });
});
