<?php

declare(strict_types=1);

use App\Modules\Tdap\Controllers\AtaController;
use App\Modules\Tdap\Controllers\CaminhaoController;
use App\Modules\Tdap\Controllers\CronoCaminhaoController;
use App\Modules\Tdap\Controllers\CronogramaComprovanteController;
use App\Modules\Tdap\Controllers\CronogramaController;
use App\Modules\Tdap\Controllers\CronoViagemController;
use App\Modules\Tdap\Controllers\LoteController;
use App\Modules\Tdap\Controllers\HistoricoController;
use App\Modules\Tdap\Controllers\PrestadorController;
use App\Modules\Tdap\Controllers\ProcessoTdapController;
use App\Modules\Tdap\Controllers\TdapDashboardController;
use App\Modules\Tdap\Controllers\VistoriaController;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Historico;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Models\ProcessoTdap;
use App\Modules\Tdap\Models\Vistoria;
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

/*
| Route::model() e GLOBAL: registra o binder para o nome do parametro em TODA a
| aplicacao, nao so nas rotas deste arquivo. `lote` e `vistoria` sairam daqui
| porque o Cisterna tambem tem /cisternas/lotes/{lote} e
| /cisternas/vistorias/{vistoria}, e o binder explicito vence o implicito no
| SubstituteBindings -- as quatro rotas de vistoria e a de lote do Cisterna
| resolviam contra o model do Tdap e devolviam 404.
|
| Nao faziam falta: os 8 handlers de Lote e Vistoria daqui type-hintam o proprio
| model, e o binding implicito ja resolve o certo em cada modulo.
|
| O bug so aparecia sem cache de rota. Com bootstrap/cache/routes-*.php este
| arquivo nao e carregado, os Route::model() nunca executam e o Cisterna
| funcionava -- ou seja, o comportamento mudava entre producao (cacheada) e
| dev/teste (nao cacheada), que e o pior lugar para uma divergencia morar.
|
| Ao acrescentar Route::model() aqui, confira se o nome do parametro e exclusivo
| deste modulo.
*/
Route::model('prestador', Prestador::class);
Route::model('caminhao', Caminhao::class);
Route::model('ata', Ata::class);
Route::model('cronograma', Cronograma::class);
Route::model('cronoCaminhao', CronoCaminhao::class);
Route::model('viagem', CronoViagem::class);
Route::model('historico', Historico::class);
Route::model('processo', ProcessoTdap::class);

Route::prefix('tdap')->name('tdap.')->group(function () {

    Route::get('/', [TdapDashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('can:tdap.dashboard.view');

    /* Prestadores (Fase 1) */
    Route::prefix('prestadores')->name('prestadores.')->group(function () {
        Route::middleware('can:tdap.prestadores.view')->group(function () {
            Route::get('/', [PrestadorController::class, 'index'])->name('index');
            Route::get('/export', [PrestadorController::class, 'export'])->name('export');
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
            Route::get('/export', [CaminhaoController::class, 'export'])->name('export');
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

    /* Atas (Fase 2) */
    Route::prefix('atas')->name('atas.')->group(function () {
        Route::middleware('can:tdap.atas.view')->group(function () {
            Route::get('/', [AtaController::class, 'index'])->name('index');
            Route::get('/export', [AtaController::class, 'export'])->name('export');
            Route::get('/{ata}', [AtaController::class, 'show'])
                ->name('show')->whereNumber('ata');
        });

        Route::middleware('can:tdap.atas.create')->group(function () {
            Route::get('/novo/cadastrar', [AtaController::class, 'create'])->name('create');
            Route::post('/', [AtaController::class, 'store'])->name('store');
        });

        Route::middleware('can:tdap.atas.edit')->group(function () {
            Route::get('/{ata}/editar', [AtaController::class, 'edit'])
                ->name('edit')->whereNumber('ata');
            Route::put('/{ata}', [AtaController::class, 'update'])
                ->name('update')->whereNumber('ata');
        });

        Route::middleware('can:tdap.atas.delete')->group(function () {
            Route::delete('/{ata}', [AtaController::class, 'destroy'])
                ->name('destroy')->whereNumber('ata');
        });
    });

    /* Lotes (Fase 2) */
    Route::prefix('lotes')->name('lotes.')->group(function () {
        Route::middleware('can:tdap.lotes.view')->group(function () {
            Route::get('/', [LoteController::class, 'index'])->name('index');
            Route::get('/export', [LoteController::class, 'export'])->name('export');
            Route::get('/{lote}', [LoteController::class, 'show'])
                ->name('show')->whereNumber('lote');
        });

        Route::middleware('can:tdap.lotes.create')->group(function () {
            Route::get('/novo/cadastrar', [LoteController::class, 'create'])->name('create');
            Route::post('/', [LoteController::class, 'store'])->name('store');
        });

        Route::middleware('can:tdap.lotes.edit')->group(function () {
            Route::get('/{lote}/editar', [LoteController::class, 'edit'])
                ->name('edit')->whereNumber('lote');
            Route::put('/{lote}', [LoteController::class, 'update'])
                ->name('update')->whereNumber('lote');
        });

        Route::middleware('can:tdap.lotes.delete')->group(function () {
            Route::delete('/{lote}', [LoteController::class, 'destroy'])
                ->name('destroy')->whereNumber('lote');
        });
    });

    /* Cronogramas (Fase 3) */
    Route::prefix('cronogramas')->name('cronogramas.')->group(function () {
        Route::middleware('can:tdap.cronogramas.view')->group(function () {
            Route::get('/', [CronogramaController::class, 'index'])->name('index');
            Route::get('/export', [CronogramaController::class, 'export'])->name('export');
            Route::get('/{cronograma}', [CronogramaController::class, 'show'])
                ->name('show')->whereNumber('cronograma');
            Route::get('/{cronograma}/comprovantes/{comprovante}/download', [CronogramaComprovanteController::class, 'download'])
                ->name('comprovantes.download')->whereNumber('cronograma')->whereNumber('comprovante');
        });

        Route::middleware('can:tdap.cronogramas.edit')->group(function () {
            Route::post('/{cronograma}/comprovantes', [CronogramaComprovanteController::class, 'store'])
                ->name('comprovantes.store')->whereNumber('cronograma');
            Route::delete('/{cronograma}/comprovantes/{comprovante}', [CronogramaComprovanteController::class, 'destroy'])
                ->name('comprovantes.destroy')->whereNumber('cronograma')->whereNumber('comprovante');
        });

        Route::middleware('can:tdap.cronogramas.create')->group(function () {
            Route::get('/novo/cadastrar', [CronogramaController::class, 'create'])->name('create');
            Route::post('/', [CronogramaController::class, 'store'])->name('store');
        });

        Route::middleware('can:tdap.cronogramas.edit')->group(function () {
            Route::get('/{cronograma}/editar', [CronogramaController::class, 'edit'])
                ->name('edit')->whereNumber('cronograma');
            Route::put('/{cronograma}', [CronogramaController::class, 'update'])
                ->name('update')->whereNumber('cronograma');
        });

        Route::middleware('can:tdap.cronogramas.ativar')->group(function () {
            Route::post('/{cronograma}/ativar', [CronogramaController::class, 'ativar'])
                ->name('ativar')->whereNumber('cronograma');
            Route::post('/{cronograma}/encerrar', [CronogramaController::class, 'encerrar'])
                ->name('encerrar')->whereNumber('cronograma');
        });

        Route::middleware('can:tdap.cronogramas.prorrogar')->group(function () {
            Route::post('/{cronograma}/prorrogar', [CronogramaController::class, 'prorrogar'])
                ->name('prorrogar')->whereNumber('cronograma');
        });

        Route::middleware('can:tdap.cronogramas.delete')->group(function () {
            Route::delete('/{cronograma}', [CronogramaController::class, 'destroy'])
                ->name('destroy')->whereNumber('cronograma');
            Route::patch('/{cronograma}/arquivar', [CronogramaController::class, 'arquivar'])
                ->name('arquivar')->whereNumber('cronograma');
            Route::patch('/{cronograma}/desarquivar', [CronogramaController::class, 'desarquivar'])
                ->name('desarquivar')->whereNumber('cronograma');
        });
    });

    /* CronoCaminhoes - subrota gerenciada pelo Cronograma (Fase 3) */
    Route::prefix('crono-caminhoes')->name('crono_caminhoes.')->group(function () {
        Route::middleware('can:tdap.cronogramas.edit')->group(function () {
            Route::post('/', [CronoCaminhaoController::class, 'store'])->name('store');
            Route::put('/{cronoCaminhao}', [CronoCaminhaoController::class, 'update'])
                ->name('update')->whereNumber('cronoCaminhao');
            Route::delete('/{cronoCaminhao}', [CronoCaminhaoController::class, 'destroy'])
                ->name('destroy')->whereNumber('cronoCaminhao');
        });
    });

    /* Viagens (Fase 3) */
    Route::prefix('viagens')->name('viagens.')->group(function () {
        Route::middleware('can:tdap.viagens.validar')->group(function () {
            Route::get('/pendentes', [CronoViagemController::class, 'pendentes'])->name('pendentes');
            Route::post('/{viagem}/validar', [CronoViagemController::class, 'validar'])
                ->name('validar')->whereNumber('viagem');
        });

        Route::middleware('can:tdap.viagens.create')->group(function () {
            Route::post('/', [CronoViagemController::class, 'store'])->name('store');
            Route::delete('/{viagem}', [CronoViagemController::class, 'destroy'])
                ->name('destroy')->whereNumber('viagem');
        });
    });

    /* Vistorias (Fase 4) */
    Route::prefix('vistorias')->name('vistorias.')->group(function () {
        Route::middleware('can:tdap.vistorias.view')->group(function () {
            Route::get('/', [VistoriaController::class, 'index'])->name('index');
            Route::get('/export', [VistoriaController::class, 'export'])->name('export');
            Route::get('/{vistoria}', [VistoriaController::class, 'show'])
                ->name('show')->whereNumber('vistoria');
        });

        Route::middleware('can:tdap.vistorias.create')->group(function () {
            Route::get('/novo/cadastrar', [VistoriaController::class, 'create'])->name('create');
            Route::post('/', [VistoriaController::class, 'store'])->name('store');
        });

        Route::middleware('can:tdap.vistorias.edit')->group(function () {
            Route::get('/{vistoria}/editar', [VistoriaController::class, 'edit'])
                ->name('edit')->whereNumber('vistoria');
            Route::put('/{vistoria}', [VistoriaController::class, 'update'])
                ->name('update')->whereNumber('vistoria');
        });

        Route::middleware('can:tdap.vistorias.delete')->group(function () {
            Route::delete('/{vistoria}', [VistoriaController::class, 'destroy'])
                ->name('destroy')->whereNumber('vistoria');
        });
    });

    /* Historico (Fase 5) - somente leitura */
    Route::prefix('historicos')->name('historicos.')->group(function () {
        Route::middleware('can:tdap.historico.view')->group(function () {
            Route::get('/', [HistoricoController::class, 'index'])->name('index');
            Route::get('/export', [HistoricoController::class, 'export'])->name('export');
            Route::get('/por-entidade', [HistoricoController::class, 'porEntidade'])->name('por_entidade');
            Route::get('/{historico}', [HistoricoController::class, 'show'])
                ->name('show')->whereNumber('historico');
        });
    });

    /* Processos / Workflow (Fase 6) */
    Route::prefix('processos')->name('processos.')->group(function () {
        Route::middleware('can:tdap.processos.view')->group(function () {
            Route::get('/', [ProcessoTdapController::class, 'index'])->name('index');
            Route::get('/export', [ProcessoTdapController::class, 'export'])->name('export');
            Route::get('/swimlanes', [ProcessoTdapController::class, 'swimlanes'])->name('swimlanes');
            Route::get('/{processo}', [ProcessoTdapController::class, 'show'])
                ->name('show')->whereUuid('processo');
        });

        Route::middleware('can:tdap.processos.create')->group(function () {
            Route::get('/novo/abrir', [ProcessoTdapController::class, 'create'])->name('create');
            Route::post('/', [ProcessoTdapController::class, 'store'])->name('store');
        });

        Route::middleware('can:tdap.processos.transitar')->group(function () {
            Route::post('/{processo}/transitar', [ProcessoTdapController::class, 'transitar'])
                ->name('transitar')->whereUuid('processo');
        });
    });
});
