<?php

declare(strict_types=1);

use App\Modules\NotasTecnicas\Controllers\NotaTecnicaArquivoController;
use App\Modules\NotasTecnicas\Controllers\NotaTecnicaCategoriaController;
use App\Modules\NotasTecnicas\Controllers\NotaTecnicaController;
use App\Modules\NotasTecnicas\Controllers\NotaTecnicaGestaoController;
use App\Modules\NotasTecnicas\Controllers\NotaTecnicaLeituraController;
use App\Modules\NotasTecnicas\Controllers\NotaTecnicaVersaoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modulo Notas Tecnicas - repositorio de notas tecnicas e POPs em PDF
|--------------------------------------------------------------------------
| Permissoes em config/permissions.php, bloco NOTAS_TECNICAS. A leitura so exige
| `view`; rascunho invisivel ao leitor e decidido pela NotaTecnicaPolicy.
|
| Sem Route::model(): o binding e GLOBAL neste projeto (ver routes/modules/cedec.php).
| Os type-hints NotaTecnica, NotaTecnicaVersao e NotaTecnicaCategoria resolvem
| {nota}, {versao} e {categoria} implicitamente.
|
| Rotas literais (/criar, /categorias, /versoes) vem antes das de {nota}.
*/
Route::prefix('notas-tecnicas')->name('notas-tecnicas.')->group(function () {
    Route::get('/', [NotaTecnicaController::class, 'index'])
        ->name('index')->middleware('can:notas_tecnicas.notas.view');

    Route::get('/criar', [NotaTecnicaGestaoController::class, 'create'])
        ->name('create')->middleware('can:notas_tecnicas.notas.create');
    Route::post('/', [NotaTecnicaGestaoController::class, 'store'])
        ->name('store')->middleware('can:notas_tecnicas.notas.create');

    Route::get('/versoes/{versao}/pdf', [NotaTecnicaArquivoController::class, 'show'])
        ->name('versoes.pdf')->middleware('can:notas_tecnicas.notas.view')
        ->whereNumber('versao');
    // Ciencia e do proprio leitor: basta `view` (a policy barra rascunho).
    Route::post('/versoes/{versao}/ciencia', [NotaTecnicaLeituraController::class, 'ciencia'])
        ->name('versoes.ciencia')->middleware('can:notas_tecnicas.notas.view')
        ->whereNumber('versao');

    Route::prefix('categorias')->name('categorias.')
        ->middleware('can:notas_tecnicas.categorias.manage')
        ->group(function () {
            Route::post('/', [NotaTecnicaCategoriaController::class, 'store'])->name('store');
            Route::put('/{categoria}', [NotaTecnicaCategoriaController::class, 'update'])
                ->name('update')->whereNumber('categoria');
            Route::delete('/{categoria}', [NotaTecnicaCategoriaController::class, 'destroy'])
                ->name('destroy')->whereNumber('categoria');
        });

    Route::prefix('{nota}')->whereNumber('nota')->group(function () {
        Route::get('/editar', [NotaTecnicaGestaoController::class, 'edit'])
            ->name('edit')->middleware('can:notas_tecnicas.notas.edit');
        Route::put('/', [NotaTecnicaGestaoController::class, 'update'])
            ->name('update')->middleware('can:notas_tecnicas.notas.edit');
        Route::post('/versoes', [NotaTecnicaVersaoController::class, 'store'])
            ->name('versoes.store')->middleware('can:notas_tecnicas.notas.edit');
        Route::post('/publicar', [NotaTecnicaGestaoController::class, 'publicar'])
            ->name('publicar')->middleware('can:notas_tecnicas.notas.publicar');
        Route::post('/revogar', [NotaTecnicaGestaoController::class, 'revogar'])
            ->name('revogar')->middleware('can:notas_tecnicas.notas.publicar');
        Route::delete('/', [NotaTecnicaGestaoController::class, 'destroy'])
            ->name('destroy')->middleware('can:notas_tecnicas.notas.delete');
        Route::get('/leituras', [NotaTecnicaLeituraController::class, 'relatorio'])
            ->name('leituras')->middleware('can:notas_tecnicas.leituras.view');
        Route::get('/leituras/exportar', [NotaTecnicaLeituraController::class, 'exportar'])
            ->name('leituras.exportar')->middleware('can:notas_tecnicas.leituras.view');
    });
});
