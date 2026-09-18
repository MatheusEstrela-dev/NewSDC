<?php

declare(strict_types=1);

use App\Modules\Tdap\Controllers\AtaController;
use App\Modules\Tdap\Controllers\CronoCaminhaoController;
use App\Modules\Tdap\Controllers\CronogramaComprovanteController;
use App\Modules\Tdap\Controllers\CronogramaController;
use App\Modules\Tdap\Controllers\CronoViagemController;
use App\Modules\Tdap\Controllers\FrotaController;
use App\Modules\Tdap\Controllers\FrotaVistoriaController;
use App\Modules\Tdap\Controllers\FrotaVistoriaFotoController;
use App\Modules\Tdap\Controllers\LoteController;
use App\Modules\Tdap\Controllers\HistoricoController;
use App\Modules\Tdap\Controllers\PrestadorController;
use App\Modules\Tdap\Controllers\TdapDashboardController;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Historico;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\Prestador;
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

    /*
    | FROTA = caminhoes + vistorias, um submodulo so.
    |
    | Eram dois grupos irmaos (`tdap/caminhoes` e `tdap/vistorias`) descrevendo o
    | mesmo objeto de negocio: o caminhao-pipa. A separacao ja tinha caido no
    | front -- a tela de caminhoes se chama "Frota e Vistorias", o breadcrumb
    | trata as duas como uma so e o menu tem item unico -- mas o backend seguia
    | partido, com a regra de vigencia escrita em quatro lugares e o endpoint de
    | vistoria morando no controller de caminhao.
    |
    | A VISTORIA E ANINHADA NO CAMINHAO porque e isso que o schema diz:
    | `tdap_vistorias.placa_id` e FK para `tdap_caminhoes.id` (nome legado -- a
    | coluna guarda o ID, nao a placa). Vistoria nao existe sem caminhao.
    |
    | O aninhamento tambem corrige um bug: a listagem mandava
    | `route('tdap.vistorias.create', { placa_id: id })`, mas o create nunca leu
    | a query string e o pre-preenchimento nunca acontecia.
    |
    | PERMISSOES continuam `tdap.caminhoes.*` e `tdap.vistorias.*`: renomear
    | exigiria migrar as atribuicoes ja gravadas em banco (Spatie) sem ganho
    | funcional. Os nomes precedem a fusao.
    */
    Route::prefix('frota')->name('frota.')->group(function () {

        /* -- Cadastro do veiculo -- */
        Route::middleware('can:tdap.caminhoes.view')->group(function () {
            Route::get('/', [FrotaController::class, 'index'])->name('index');
            Route::get('/export', [FrotaController::class, 'export'])->name('export');
        });

        Route::middleware('can:tdap.caminhoes.create')->group(function () {
            Route::get('/novo/cadastrar', [FrotaController::class, 'create'])->name('create');
            Route::post('/', [FrotaController::class, 'store'])->name('store');
        });

        /*
        | Vistorias, declaradas ANTES de `/{caminhao}`: o segmento literal
        | `vistorias` casaria com o parametro. O whereNumber ja protegeria, mas
        | a ordem explicita evita depender disso.
        */
        Route::prefix('vistorias')->name('vistorias.')->group(function () {
            Route::middleware('can:tdap.vistorias.view')->group(function () {
                Route::get('/', [FrotaVistoriaController::class, 'index'])->name('index');
                Route::get('/export', [FrotaVistoriaController::class, 'export'])->name('export');
                Route::get('/{vistoria}', [FrotaVistoriaController::class, 'show'])
                    ->name('show')->whereNumber('vistoria');

                // Serve a imagem da miniatura: o disco 'tdap' e privado e nao
                // tem URL publica.
                Route::get('/{vistoria}/fotos/{foto}', [FrotaVistoriaFotoController::class, 'show'])
                    ->name('fotos.show')->whereNumber('vistoria')->whereNumber('foto');
            });

            Route::middleware('can:tdap.vistorias.edit')->group(function () {
                Route::get('/{vistoria}/editar', [FrotaVistoriaController::class, 'edit'])
                    ->name('edit')->whereNumber('vistoria');
                Route::put('/{vistoria}', [FrotaVistoriaController::class, 'update'])
                    ->name('update')->whereNumber('vistoria');

                Route::post('/{vistoria}/fotos', [FrotaVistoriaFotoController::class, 'store'])
                    ->name('fotos.store')->whereNumber('vistoria');
                Route::delete('/{vistoria}/fotos/{foto}', [FrotaVistoriaFotoController::class, 'destroy'])
                    ->name('fotos.destroy')->whereNumber('vistoria')->whereNumber('foto');
            });

            Route::middleware('can:tdap.vistorias.delete')->group(function () {
                Route::delete('/{vistoria}', [FrotaVistoriaController::class, 'destroy'])
                    ->name('destroy')->whereNumber('vistoria');
            });
        });

        /* -- Vistoria a partir de um caminhao -- */
        Route::middleware('can:tdap.vistorias.view')->group(function () {
            // JSON, nao Inertia: alimenta o modal de serie historica na propria
            // listagem da frota. Sair da tela para consultar o historico fazia
            // o operador perder filtro, pagina e posicao de rolagem -- e ele
            // consulta caminhao a caminhao.
            //
            // Sob `tdap.vistorias.view`, e nao `tdap.caminhoes.view` como era
            // quando morava no controller de caminhao: sao dados de vistoria.
            Route::get('/{caminhao}/vistorias', [FrotaVistoriaController::class, 'vistoriasDoCaminhao'])
                ->name('vistorias.do-caminhao')->whereNumber('caminhao');
        });

        Route::middleware('can:tdap.vistorias.create')->group(function () {
            Route::get('/{caminhao}/vistorias/nova', [FrotaVistoriaController::class, 'create'])
                ->name('vistorias.create')->whereNumber('caminhao');
            Route::post('/{caminhao}/vistorias', [FrotaVistoriaController::class, 'store'])
                ->name('vistorias.store')->whereNumber('caminhao');
        });

        /* -- Ficha do veiculo (rotas com parametro por ultimo) -- */
        Route::middleware('can:tdap.caminhoes.view')->group(function () {
            Route::get('/{caminhao}', [FrotaController::class, 'show'])
                ->name('show')->whereNumber('caminhao');
        });

        Route::middleware('can:tdap.caminhoes.edit')->group(function () {
            Route::get('/{caminhao}/editar', [FrotaController::class, 'edit'])
                ->name('edit')->whereNumber('caminhao');
            Route::put('/{caminhao}', [FrotaController::class, 'update'])
                ->name('update')->whereNumber('caminhao');
        });

        Route::middleware('can:tdap.caminhoes.delete')->group(function () {
            Route::delete('/{caminhao}', [FrotaController::class, 'destroy'])
                ->name('destroy')->whereNumber('caminhao');
        });
    });

    /*
    | Enderecos antigos.
    |
    | `tdap/caminhoes` e `tdap/vistorias` circularam em e-mail, oficio e favorito
    | de navegador. 301 em vez de simplesmente sumir: link que morre em 404 vira
    | chamado de suporte, e quem clicou nao tem como adivinhar o endereco novo.
    |
    | Sem middleware de permissao: o destino ja cobra a permissao, e negar aqui
    | trocaria o redirect por um 403 sem explicacao.
    */
    Route::redirect('/caminhoes', '/tdap/frota', 301);
    Route::redirect('/caminhoes/{resto}', '/tdap/frota/{resto}', 301)->where('resto', '.*');
    Route::redirect('/vistorias', '/tdap/frota/vistorias', 301);
    Route::redirect('/vistorias/{resto}', '/tdap/frota/vistorias/{resto}', 301)->where('resto', '.*');

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

        // Confirmacao do municipio: ato distinto da validacao da CEDEC, e por
        // isso com slug proprio. O recorte por municipio nao esta aqui -- vive
        // no service, porque middleware nao filtra linha.
        Route::middleware('can:tdap.viagens.confirmar')->group(function () {
            Route::get('/confirmacao', [CronoViagemController::class, 'confirmacao'])->name('confirmacao');
            Route::post('/confirmar-lote', [CronoViagemController::class, 'confirmarLote'])->name('confirmar-lote');
        });

        Route::middleware('can:tdap.viagens.create')->group(function () {
            Route::post('/', [CronoViagemController::class, 'store'])->name('store');
            Route::delete('/{viagem}', [CronoViagemController::class, 'destroy'])
                ->name('destroy')->whereNumber('viagem');
        });
    });

    /* Vistorias: ver o grupo `frota` acima -- foram aninhadas no caminhao. */

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
});
