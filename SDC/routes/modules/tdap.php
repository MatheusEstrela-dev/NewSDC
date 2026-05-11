<?php

declare(strict_types=1);

use App\Modules\Tdap\Controllers\TdapDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TDAP - Transporte e Distribuicao de Agua Potavel
|--------------------------------------------------------------------------
|
| Gestao de prestadores, caminhoes-tanque, atas, lotes, cronogramas de
| fornecimento, viagens, vistorias e historico de auditoria.
|
| Carregado por routes/web.php dentro do middleware 'auth'. Endpoints
| especificos sao adicionados a partir da Fase 1 (Prestador+Caminhao).
|
| Plano: docs/superpowers/plans/2026-05-11-tdap-migration.md
*/

Route::prefix('tdap')->name('tdap.')->group(function () {
    Route::get('/', [TdapDashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('can:tdap.dashboard.view');
});
