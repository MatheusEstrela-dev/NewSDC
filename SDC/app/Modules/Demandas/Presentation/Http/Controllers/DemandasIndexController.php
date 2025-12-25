<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Application\UseCases\GetTaskStatisticsUseCase;
use App\Modules\Demandas\Application\UseCases\ListTasksUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Demandas Index
 *
 * Portal do Usuário e Console do Agente
 */
class DemandasIndexController extends Controller
{
    public function __construct(
        private readonly ListTasksUseCase $listTasksUseCase,
        private readonly GetTaskStatisticsUseCase $getStatisticsUseCase
    ) {
    }

    /**
     * Portal do Usuário - Lista suas próprias demandas
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $filters = $request->only([
            'protocolo',
            'status',
            'tipo',
            'data_inicio',
            'data_fim',
            'search',
        ]);

        // Usuários comuns veem apenas suas solicitações
        $statistics = $this->getStatisticsUseCase->execute($filters, $user);
        $tasks = $this->listTasksUseCase->executeAsDTO($filters, $user, 15);

        return Inertia::render('Demandas/DemandasIndex', [
            'statistics' => $statistics->toArray(),
            'tasks' => $tasks['data'],
            'pagination' => $tasks['pagination'],
            'filters' => $filters,
            'canManage' => $user->can('demandas.manage'),
        ]);
    }

    /**
     * Console do Agente - Todas as demandas
     */
    public function adminIndex(Request $request): Response
    {
        $user = $request->user();

        // Verificar permissão
        $this->authorize('demandas.manage');

        $filters = $request->only([
            'protocolo',
            'status',
            'tipo',
            'prioridade',
            'categoria',
            'atribuido_para_id',
            'solicitante_id',
            'data_inicio',
            'data_fim',
            'atrasadas',
            'minhas_tasks',
            'search',
        ]);

        $statistics = $this->getStatisticsUseCase->execute($filters, $user);
        $tasks = $this->listTasksUseCase->executeAsDTO($filters, $user, 15);

        return Inertia::render('Admin/Demandas/DemandasAdminIndex', [
            'statistics' => $statistics->toArray(),
            'tasks' => $tasks['data'],
            'pagination' => $tasks['pagination'],
            'filters' => $filters,
        ]);
    }

    /**
     * Atribuir demanda a um agente
     */
    public function assign(Request $request, int $id)
    {
        $this->authorize('demandas.assign');

        // TODO: Implementar lógica de atribuição

        return redirect()->back()->with('success', 'Demanda atribuída com sucesso!');
    }

    /**
     * Alterar status da demanda
     */
    public function changeStatus(Request $request, int $id)
    {
        $this->authorize('demandas.change-status');

        // TODO: Implementar lógica de mudança de status

        return redirect()->back()->with('success', 'Status alterado com sucesso!');
    }
}
