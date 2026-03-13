<?php

use App\Modules\Compdec\Controllers\OrgaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('compdec')->name('compdec.')->group(function () {

    Route::get('/orgaos', [OrgaoController::class, 'index'])->name('index');
    Route::get('/orgaos/{id}', [OrgaoController::class, 'show'])
        ->name('show')
        ->where('id', '[0-9]+');

    Route::middleware('can:compdec.manage')->group(function () {
        Route::get('/orgaos/novo', [OrgaoController::class, 'create'])->name('create');
        Route::post('/orgaos', [OrgaoController::class, 'store'])->name('store');
        Route::get('/orgaos/{id}/editar', [OrgaoController::class, 'edit'])
            ->name('edit')
            ->where('id', '[0-9]+');
        Route::put('/orgaos/{id}', [OrgaoController::class, 'update'])
            ->name('update')
            ->where('id', '[0-9]+');
        Route::delete('/orgaos/{id}', [OrgaoController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9]+');
        Route::post('/orgaos/{id}/usuarios', [OrgaoController::class, 'vincularUsuario'])
            ->name('usuarios.vincular')
            ->where('id', '[0-9]+');
    });
});


Route::prefix('compdec')->name('compdec.')->group(function () {

    Route::get('/orgaos', [OrgaoController::class, 'index'])->name('index');
    Route::get('/orgaos/{id}', [OrgaoController::class, 'show'])
        ->name('show')
        ->where('id', '[0-9]+');

    Route::middleware('can:compdec.manage')->group(function () {
        Route::get('/orgaos/novo', [OrgaoController::class, 'create'])->name('create');
        Route::post('/orgaos', [OrgaoController::class, 'store'])->name('store');
        Route::get('/orgaos/{id}/editar', [OrgaoController::class, 'edit'])
            ->name('edit')
            ->where('id', '[0-9]+');
        Route::put('/orgaos/{id}', [OrgaoController::class, 'update'])
            ->name('update')
            ->where('id', '[0-9]+');
        Route::delete('/orgaos/{id}', [OrgaoController::class, 'destroy'])
            ->name('destroy')
            ->where('id', '[0-9]+');
        Route::post('/orgaos/{id}/usuarios', [OrgaoController::class, 'vincularUsuario'])
            ->name('usuarios.vincular')
            ->where('id', '[0-9]+');
    });

    // Módulo: RAT (Registro de Atendimento Técnico — Compdec)
    Route::prefix('rat')->name('rat.')->group(function () {
        // CRUD principal de ocorrências
        Route::get('/', [RatController::class, 'index'])->name('index');
        Route::get('/exportar', [RatController::class, 'exportRats'])->name('export');
        Route::get('/novo', [RatController::class, 'create'])->name('create');
        Route::post('/', [RatController::class, 'store'])->name('store');
        Route::get('/{ocorrencia}', [RatController::class, 'show'])->name('show');
        Route::get('/{ocorrencia}/editar', [RatController::class, 'edit'])->name('edit');
        Route::put('/{ocorrencia}', [RatController::class, 'update'])->name('update');
        Route::delete('/{ocorrencia}', [RatController::class, 'destroy'])->name('destroy');
        Route::patch('/{ocorrencia}/finalizar', [RatController::class, 'finalize'])->name('finalize');

        // BOs
        Route::prefix('bo')->name('bo.')->group(function () {
            Route::get('/', [BoRatController::class, 'index'])->name('index');
            Route::post('/', [BoRatController::class, 'store'])->name('store');
        });

        // Ocorrências detalhadas com sub-recursos
        Route::prefix('ocorrencias')->name('ocorrencias.')->group(function () {
            Route::get('/', [RatOcorrenciaController::class, 'index'])->name('index');
            Route::post('/', [RatOcorrenciaController::class, 'store'])->name('store');
            Route::get('/{ocorrencia}', [RatOcorrenciaController::class, 'show'])->name('show');
            Route::patch('/{ocorrencia}/finalizar', [RatOcorrenciaController::class, 'finalize'])->name('finalize');

            // Vistoria técnica
            Route::prefix('{ocorrencia}/vistoria')->name('vistoria.')->group(function () {
                Route::get('/', [RatVistoriaController::class, 'show'])->name('show');
                Route::post('/', [RatVistoriaController::class, 'store'])->name('store');
                Route::put('/{vistoria}', [RatVistoriaController::class, 'update'])->name('update');
            });

            // Recursos empregados
            Route::prefix('{ocorrencia}/recursos')->name('recursos.')->group(function () {
                Route::get('/', [RatRecursoController::class, 'index'])->name('index');
                Route::post('/', [RatRecursoController::class, 'store'])->name('store');
                Route::put('/{recurso}', [RatRecursoController::class, 'update'])->name('update');
                Route::delete('/{recurso}', [RatRecursoController::class, 'destroy'])->name('destroy');
            });

            // Envolvidos
            Route::prefix('{ocorrencia}/envolvidos')->name('envolvidos.')->group(function () {
                Route::get('/', [RatEnvolvidosController::class, 'index'])->name('index');
                Route::post('/', [RatEnvolvidosController::class, 'store'])->name('store');
                Route::put('/{envolvido}', [RatEnvolvidosController::class, 'update'])->name('update');
                Route::delete('/{envolvido}', [RatEnvolvidosController::class, 'destroy'])->name('destroy');
            });
        });

        // Alvos (locais/endereços de interesse)
        Route::prefix('alvos')->name('alvos.')->group(function () {
            Route::get('/', [RatAlvoController::class, 'index'])->name('index');
            Route::get('/{ocorrencia}', [RatAlvoController::class, 'show'])->name('show');
        });
    });
});
