<?php

declare(strict_types=1);

use App\Modules\Compdec\Controllers\EquipeController;
use App\Modules\Compdec\Controllers\OrgaoController;
use App\Modules\Compdec\Controllers\PrefeituraController;
use App\Modules\Compdec\Models\CompdecEquipe;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modulo COMPDEC - Orgaos de Defesa Civil
|--------------------------------------------------------------------------
| Permissoes em config/permissions.php (grupo COMPDEC).
| Threshold de queries em config/compdec.php (default 15).
|
| Sub-recursos da F1: Prefeitura.
| Sub-recursos das F2-F4 (equipe, anexos, plano) sao adicionados
| em PRs subsequentes.
*/

Route::model('orgao', Orgao::class);
Route::model('equipe', CompdecEquipe::class);

Route::middleware(['auth', 'compdec.query-threshold:' . config('compdec.query_threshold', 15)])
    ->prefix('compdec')
    ->name('compdec.')
    ->group(function () {

        // Listagem e visualizacao
        Route::middleware('can:compdec.orgaos.view')->group(function () {
            Route::get('/orgaos', [OrgaoController::class, 'index'])->name('index');
            Route::get('/orgaos/arvore', [OrgaoController::class, 'arvore'])
                ->middleware('compdec.query-threshold:25')
                ->name('arvore');
            Route::get('/orgaos/{orgao}', [OrgaoController::class, 'show'])
                ->name('show')
                ->whereNumber('orgao');
        });

        // Criacao
        Route::middleware('can:compdec.orgaos.create')->group(function () {
            Route::get('/orgaos/novo', [OrgaoController::class, 'create'])->name('create');
            Route::post('/orgaos', [OrgaoController::class, 'store'])->name('store');
        });

        // Edicao + Foto coordenador
        Route::middleware('can:compdec.orgaos.edit')->group(function () {
            Route::get('/orgaos/{orgao}/editar', [OrgaoController::class, 'edit'])
                ->name('edit')
                ->whereNumber('orgao');
            Route::put('/orgaos/{orgao}', [OrgaoController::class, 'update'])
                ->name('update')
                ->whereNumber('orgao');
            Route::post('/orgaos/{orgao}/foto', [OrgaoController::class, 'uploadFotoCoordenador'])
                ->name('foto.upload')
                ->whereNumber('orgao');
            Route::delete('/orgaos/{orgao}/foto', [OrgaoController::class, 'removerFotoCoordenador'])
                ->name('foto.destroy')
                ->whereNumber('orgao');
        });

        // Exclusao
        Route::middleware('can:compdec.orgaos.delete')->group(function () {
            Route::delete('/orgaos/{orgao}', [OrgaoController::class, 'destroy'])
                ->name('destroy')
                ->whereNumber('orgao');
        });

        // Vinculo de usuarios ao orgao (compdec_orgao_user)
        Route::middleware('can:compdec.usuarios.manage')->group(function () {
            Route::post('/orgaos/{orgao}/usuarios', [OrgaoController::class, 'vincularUsuario'])
                ->name('usuarios.vincular')
                ->whereNumber('orgao');
            Route::delete('/orgaos/{orgao}/usuarios/{usuario}', [OrgaoController::class, 'desvincularUsuario'])
                ->name('usuarios.desvincular')
                ->whereNumber('orgao')
                ->whereNumber('usuario');
        });

        // Sub-recurso: Prefeitura (1:1 via municipio_id do orgao)
        Route::middleware('can:compdec.prefeitura.view')->group(function () {
            Route::get('/orgaos/{orgao}/prefeitura', [PrefeituraController::class, 'show'])
                ->name('prefeitura.show')
                ->whereNumber('orgao');
        });

        Route::middleware('can:compdec.prefeitura.edit')->group(function () {
            Route::match(['post', 'put'], '/orgaos/{orgao}/prefeitura', [PrefeituraController::class, 'upsert'])
                ->name('prefeitura.upsert')
                ->whereNumber('orgao');
            Route::post('/orgaos/{orgao}/prefeitura/foto', [PrefeituraController::class, 'uploadFoto'])
                ->name('prefeitura.foto.upload')
                ->whereNumber('orgao');
            Route::delete('/orgaos/{orgao}/prefeitura/foto', [PrefeituraController::class, 'removerFoto'])
                ->name('prefeitura.foto.destroy')
                ->whereNumber('orgao');
        });

        // Sub-recurso: Equipe (1:N por orgao, scoped binding)
        Route::scopeBindings()->group(function () {
            Route::middleware('can:compdec.equipe.view')->group(function () {
                Route::get('/orgaos/{orgao}/equipe', [EquipeController::class, 'index'])
                    ->name('equipe.index')
                    ->whereNumber('orgao');
                Route::get('/orgaos/{orgao}/equipe/{equipe}', [EquipeController::class, 'show'])
                    ->name('equipe.show')
                    ->whereNumber('orgao')->whereNumber('equipe');
            });

            Route::middleware('can:compdec.equipe.create')->group(function () {
                Route::post('/orgaos/{orgao}/equipe', [EquipeController::class, 'store'])
                    ->name('equipe.store')
                    ->whereNumber('orgao');
            });

            Route::middleware('can:compdec.equipe.edit')->group(function () {
                Route::put('/orgaos/{orgao}/equipe/{equipe}', [EquipeController::class, 'update'])
                    ->name('equipe.update')
                    ->whereNumber('orgao')->whereNumber('equipe');
                Route::post('/orgaos/{orgao}/equipe/{equipe}/restaurar', [EquipeController::class, 'restore'])
                    ->name('equipe.restore')
                    ->whereNumber('orgao')->whereNumber('equipe');
            });

            Route::middleware('can:compdec.equipe.delete')->group(function () {
                Route::delete('/orgaos/{orgao}/equipe/{equipe}', [EquipeController::class, 'destroy'])
                    ->name('equipe.destroy')
                    ->whereNumber('orgao')->whereNumber('equipe');
            });
        });

    });
