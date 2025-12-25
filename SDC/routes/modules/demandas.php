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
    // PORTAL DO USUÁRIO (Self-Service)
    // ========================================

    // Página principal - Lista as demandas do usuário
    Route::get('/demandas', [DemandasIndexController::class, 'index'])
        ->name('demandas.index');

    // Criar nova demanda (formulário)
    Route::get('/demandas/nova', [TaskCreateController::class, 'create'])
        ->name('demandas.create');

    // Salvar nova demanda
    Route::post('/demandas', [TaskCreateController::class, 'store'])
        ->name('demandas.store');

    // Ver detalhes de uma demanda específica
    Route::get('/demandas/{id}', [TaskShowController::class, 'show'])
        ->name('demandas.show');

    // Adicionar comentário em uma demanda
    Route::post('/demandas/{id}/comentarios', [TaskShowController::class, 'addComment'])
        ->name('demandas.comments.store');

    // Upload de anexo
    Route::post('/demandas/{id}/anexos', [TaskShowController::class, 'addAttachment'])
        ->name('demandas.attachments.store');

    // ========================================
    // CONSOLE DO AGENTE TI
    // ========================================

    Route::middleware(['can:demandas.manage'])->prefix('admin/demandas')->name('admin.demandas.')->group(function () {

        // Dashboard de gestão (todas as demandas)
        Route::get('/', [DemandasIndexController::class, 'adminIndex'])
            ->name('index');

        // Atribuir demanda a um agente
        Route::post('/{id}/atribuir', [DemandasIndexController::class, 'assign'])
            ->name('assign');

        // Alterar status
        Route::post('/{id}/status', [DemandasIndexController::class, 'changeStatus'])
            ->name('change-status');

        // Editar demanda (apenas agentes)
        Route::get('/{id}/editar', [TaskShowController::class, 'edit'])
            ->name('edit');

        Route::put('/{id}', [TaskShowController::class, 'update'])
            ->name('update');

        // Deletar demanda (soft delete)
        Route::delete('/{id}', [TaskShowController::class, 'destroy'])
            ->name('destroy');
    });
});
