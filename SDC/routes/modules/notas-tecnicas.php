<?php

declare(strict_types=1);

use App\Modules\NotasTecnicas\Controllers\NotaTecnicaArquivoController;
use App\Modules\NotasTecnicas\Controllers\NotaTecnicaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modulo Notas Tecnicas - repositorio de notas tecnicas e POPs em PDF
|--------------------------------------------------------------------------
| Permissoes em config/permissions.php, bloco NOTAS_TECNICAS. A rota so exige
| `view`; rascunho invisivel ao leitor e decidido pela NotaTecnicaPolicy.
|
| Sem Route::model(): o binding e GLOBAL neste projeto (ver routes/modules/cedec.php).
| O type-hint NotaTecnicaVersao resolve {versao} implicitamente.
*/
Route::prefix('notas-tecnicas')->name('notas-tecnicas.')->group(function () {
    Route::get('/', [NotaTecnicaController::class, 'index'])
        ->name('index')->middleware('can:notas_tecnicas.notas.view');

    Route::get('/versoes/{versao}/pdf', [NotaTecnicaArquivoController::class, 'show'])
        ->name('versoes.pdf')->middleware('can:notas_tecnicas.notas.view')
        ->whereNumber('versao');
});
