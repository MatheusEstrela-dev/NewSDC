<?php

use App\Modules\Rat\Controllers\RatBoController;
use App\Modules\Rat\Controllers\RatAlvoController;
use App\Modules\Rat\Controllers\RatOcorrenciaController;
use App\Modules\Rat\Controllers\RatDadosGeraisController;
use App\Modules\Rat\Controllers\RatEnvolvidosController;
use App\Modules\Rat\Controllers\RatRecursoController;
use App\Modules\Rat\Controllers\RatVistoriaController;
use App\Modules\Rat\Controllers\RatHistoricoController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RAT — Nova Estrutura: RatOcorrencia + Relatos Polimórficos
// Controllers localizados em: app\Modules\Rat\Controllers
// Models localizados em: app\Modules\Rat\Models
// ============================================================================

Route::prefix('rat')->name('rat.')->group(function () {

    // ====================================================================
    // OCORRÊNCIAS — CRUD Principal
    // ====================================================================

    // Lista todas as ocorrências
    Route::get('/', [RatOcorrenciaController::class, 'index'])
        ->name('index')
        ->middleware('can:rat.protocolos.view');

    // Formulário de criação
    Route::get('/create', [RatOcorrenciaController::class, 'create'])
        ->name('create')
        ->middleware('can:rat.protocolos.create');

    // Salva nova ocorrência
    Route::post('/', [RatOcorrenciaController::class, 'store'])
        ->name('store')
        ->middleware('can:rat.protocolos.create');

    // Exibe uma ocorrência
    Route::get('/{ocorrencia}', [RatOcorrenciaController::class, 'show'])
        ->name('show')
        ->middleware('can:rat.protocolos.view');

    // Formulário de edição
    Route::get('/{ocorrencia}/edit', [RatOcorrenciaController::class, 'edit'])
        ->name('edit')
        ->middleware('can:rat.protocolos.edit');

    // Atualiza uma ocorrência
    Route::put('/{ocorrencia}', [RatOcorrenciaController::class, 'update'])
        ->name('update')
        ->middleware('can:rat.protocolos.edit');

    // Remove uma ocorrência
    Route::delete('/{ocorrencia}', [RatOcorrenciaController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:rat.protocolos.delete');

    // Finaliza uma ocorrência
    Route::patch('/{ocorrencia}/finalize', [RatOcorrenciaController::class, 'finalize'])
        ->name('finalize')
        ->middleware('can:rat.protocolos.finalize');

    // ====================================================================
    // DADOS GERAIS — CRUD
    // ====================================================================

    Route::prefix('{ocorrencia}/dados-gerais')->name('dados-gerais.')->group(function () {
        Route::get('/', [RatDadosGeraisController::class, 'show'])
            ->name('show')
            ->middleware('can:rat.protocolos.view');

        Route::post('/', [RatDadosGeraisController::class, 'store'])
            ->name('store')
            ->middleware('can:rat.relatos.manage');

        Route::put('/{dadosGerais}', [RatDadosGeraisController::class, 'update'])
            ->name('update')
            ->middleware('can:rat.relatos.manage');
    });

    // ====================================================================
    // ENVOLVIDOS — CRUD
    // ====================================================================

    Route::prefix('{ocorrencia}/envolvidos')->name('envolvidos.')->group(function () {
        Route::get('/', [RatEnvolvidosController::class, 'index'])
            ->name('index')
            ->middleware('can:rat.protocolos.view');

        Route::get('/create', [RatEnvolvidosController::class, 'create'])
            ->name('create')
            ->middleware('can:rat.relatos.manage');

        Route::post('/', [RatEnvolvidosController::class, 'store'])
            ->name('store')
            ->middleware('can:rat.relatos.manage');

        Route::get('/{envolvido}/edit', [RatEnvolvidosController::class, 'edit'])
            ->name('edit')
            ->middleware('can:rat.relatos.manage');

        Route::put('/{envolvido}', [RatEnvolvidosController::class, 'update'])
            ->name('update')
            ->middleware('can:rat.relatos.manage');

        Route::delete('/{envolvido}', [RatEnvolvidosController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:rat.relatos.manage');
    });

    // ====================================================================
    // RECURSOS — CRUD
    // ====================================================================

    Route::prefix('{ocorrencia}/recursos')->name('recursos.')->group(function () {
        Route::get('/', [RatRecursoController::class, 'index'])
            ->name('index')
            ->middleware('can:rat.protocolos.view');

        Route::get('/create', [RatRecursoController::class, 'create'])
            ->name('create')
            ->middleware('can:rat.relatos.manage');

        Route::post('/', [RatRecursoController::class, 'store'])
            ->name('store')
            ->middleware('can:rat.relatos.manage');

        Route::get('/{recurso}/edit', [RatRecursoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:rat.relatos.manage');

        Route::put('/{recurso}', [RatRecursoController::class, 'update'])
            ->name('update')
            ->middleware('can:rat.relatos.manage');

        Route::delete('/{recurso}', [RatRecursoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:rat.relatos.manage');
    });

    // ====================================================================
    // VISTORIAS — CRUD
    // ====================================================================

    Route::prefix('{ocorrencia}/vistorias')->name('vistorias.')->group(function () {
        Route::get('/', [RatVistoriaController::class, 'show'])
            ->name('show')
            ->middleware('can:rat.protocolos.view');

        Route::get('/create', [RatVistoriaController::class, 'create'])
            ->name('create')
            ->middleware('can:rat.relatos.manage');

        Route::post('/', [RatVistoriaController::class, 'store'])
            ->name('store')
            ->middleware('can:rat.relatos.manage');

        Route::get('/{vistoria}/edit', [RatVistoriaController::class, 'edit'])
            ->name('edit')
            ->middleware('can:rat.relatos.manage');

        Route::put('/{vistoria}', [RatVistoriaController::class, 'update'])
            ->name('update')
            ->middleware('can:rat.relatos.manage');

        Route::delete('/{vistoria}', [RatVistoriaController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:rat.relatos.manage');
    });

    // ====================================================================
    // HISTÓRICO
    // ====================================================================

    Route::get('/{ocorrencia}/historico', [RatHistoricoController::class, 'show'])
        ->name('historico.show')
        ->middleware('can:rat.protocolos.view');

    // ====================================================================
    // ALVOS (Endereços/Locais de Interesse)
    // ====================================================================

    Route::prefix('alvos')->name('alvos.')->group(function () {
        Route::get('/', [RatAlvoController::class, 'index'])
            ->name('index')
            ->middleware('can:rat.protocolos.view');

        Route::get('/{ocorrencia}', [RatAlvoController::class, 'show'])
            ->name('show')
            ->middleware('can:rat.protocolos.view');
    });

    // ====================================================================
    // BOLETIM DE OCORRÊNCIA
    // ====================================================================

    Route::prefix('bo')->name('bo.')->group(function () {
        Route::get('/', [RatBoController::class, 'index'])
            ->name('index')
            ->middleware('can:rat.protocolos.view');

        Route::post('/', [RatBoController::class, 'store'])
            ->name('store')
            ->middleware('can:rat.protocolos.create');
    });
});

