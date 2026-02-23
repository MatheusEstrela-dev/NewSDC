<?php

use App\Modules\Demandas\Presentation\Http\Controllers\DemandasIndexController;
use App\Modules\Demandas\Presentation\Http\Controllers\TaskCreateController;
use App\Modules\Demandas\Presentation\Http\Controllers\TaskShowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do Módulo Demandas
|--------------------------------------------------------------------------
|
| Portal Self-Service: Todos os usuários autenticados podem abrir chamados
| Console do Agente: Apenas usuários com permissão 'demandas.manage'
|
*/

Route::middleware(['web', 'auth'])->group(function () {

    // ========================================
    // PORTAL DO USUARIO (Self-Service)
    // ========================================

    // Pagina principal - Lista as demandas do usuario
    Route::get('/demandas', [DemandasIndexController::class, 'index'])
        ->name('demandas.index')
        ->middleware('can:demandas.chamados.view');

    // Criar nova demanda (formulario)
    Route::get('/demandas/nova', [TaskCreateController::class, 'create'])
        ->name('demandas.create')
        ->middleware('can:demandas.chamados.create');

    // Salvar nova demanda
    Route::post('/demandas', [TaskCreateController::class, 'store'])
        ->name('demandas.store')
        ->middleware('can:demandas.chamados.create');

    // Ver detalhes de uma demanda especifica
    Route::get('/demandas/{id}', [TaskShowController::class, 'show'])
        ->name('demandas.show')
        ->middleware('can:demandas.chamados.view');

    // Adicionar comentario em uma demanda
    Route::post('/demandas/{id}/comentarios', [TaskShowController::class, 'addComment'])
        ->name('demandas.comments.store')
        ->middleware('can:demandas.chamados.view');

    // Upload de anexo
    Route::post('/demandas/{id}/anexos', [TaskShowController::class, 'addAttachment'])
        ->name('demandas.attachments.store')
        ->middleware('can:demandas.chamados.view');

    // ========================================
    // CONSOLE DO AGENTE TI
    // ========================================

    Route::prefix('admin/demandas')->name('admin.demandas.')->group(function () {

        // Exportar
        Route::get('/export', [DemandasIndexController::class, 'export'])
            ->name('export')
            ->middleware('can:demandas.chamados.export');

        // Dashboard de gestao (todas as demandas)
        Route::get('/', [DemandasIndexController::class, 'adminIndex'])
            ->name('index')
            ->middleware('can:demandas.chamados.manage');

        // Atribuir demanda a um agente
        Route::post('/{id}/atribuir', [DemandasIndexController::class, 'assign'])
            ->name('assign')
            ->middleware('can:demandas.chamados.manage');

        // Alterar status
        Route::post('/{id}/status', [DemandasIndexController::class, 'changeStatus'])
            ->name('change-status')
            ->middleware('can:demandas.chamados.edit');

        // Editar demanda (apenas agentes)
        Route::get('/{id}/editar', [TaskShowController::class, 'edit'])
            ->name('edit')
            ->middleware('can:demandas.chamados.edit');

        Route::put('/{id}', [TaskShowController::class, 'update'])
            ->name('update')
            ->middleware('can:demandas.chamados.edit');

        // Deletar demanda (soft delete)
        Route::delete('/{id}', [TaskShowController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:demandas.chamados.delete');
    });
});
