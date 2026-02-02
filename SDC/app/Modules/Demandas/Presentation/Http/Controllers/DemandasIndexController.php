<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Application\UseCases\GetTaskStatisticsUseCase;
use App\Modules\Demandas\Application\UseCases\ListTasksUseCase;
use App\Modules\Demandas\Application\UseCases\AssignTaskUseCase;
use App\Modules\Demandas\Application\UseCases\ChangeTaskStatusUseCase;
use App\Modules\Demandas\Presentation\Http\Requests\AssignTaskRequest;
use App\Modules\Demandas\Presentation\Http\Requests\ChangeTaskStatusRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\Export\CsvExportService;

/**
 * Controller: Demandas Index
 *
 * Portal do Usuário e Console do Agente
 */
class DemandasIndexController extends Controller
{
    public function __construct(
        private readonly ListTasksUseCase $listTasksUseCase,
        private readonly GetTaskStatisticsUseCase $getStatisticsUseCase,
        private readonly AssignTaskUseCase $assignTaskUseCase,
        private readonly ChangeTaskStatusUseCase $changeTaskStatusUseCase,
        private readonly CsvExportService $csvExportService
    ) {
    }

    /**
     * Portal do Usuário - Lista suas próprias demandas
     */
    public function index(Request $request): Response
    {
        try {
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
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao carregar demandas. Por favor, tente novamente.');
        }
    }

    /**
     * Console do Agente - Todas as demandas
     */
    public function adminIndex(Request $request): Response
    {
        try {
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
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao carregar demandas. Por favor, tente novamente.');
        }
    }

    /**
     * Atribuir demanda a um agente
     */
    public function assign(AssignTaskRequest $request, int $id)
    {
        try {
            $validated = $request->validated();

            $this->assignTaskUseCase->execute($id, $validated['atribuido_para_id']);

            return redirect()->back()->with('success', 'Demanda atribuída com sucesso!');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao atribuir demanda. Por favor, tente novamente.');
        }
    }

    /**
     * Alterar status da demanda
     */
    public function changeStatus(ChangeTaskStatusRequest $request, int $id)
    {
        try {
            $validated = $request->validated();

            $this->changeTaskStatusUseCase->execute($id, $validated['status']);

            return redirect()->back()->with('success', 'Status alterado com sucesso!');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao alterar status. Por favor, tente novamente.');
        }
    }

    /**
     * Exportar demandas para CSV
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();

        // Mesmos filtros do Index e AdminIndex
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

        // Reutilizar caso de uso de listagem com -1 para pegar todos
        $tasksResult = $this->listTasksUseCase->executeAsDTO($filters, $user, -1);
        $tasks = $tasksResult['data'];

        $headers = [
            'ID',
            'Protocolo',
            'Título',
            'Tipo',
            'Prioridade',
            'Status',
            'Solicitante',
            'Responsável',
            'Data Criação'
        ];

        $mapper = function ($task) {
            return [
                $task['id'] ?? '',
                $task['protocolo'] ?? '',
                $task['titulo'] ?? '',
                $task['tipo'] ?? '',
                $task['prioridade'] ?? '',
                $task['status'] ?? '',
                $task['solicitante']['name'] ?? '',
                $task['tecnico_responsavel']['name'] ?? '',
                $task['created_at'] ?? ''
            ];
        };

        return $this->csvExportService->export($tasks, $headers, $mapper, 'demandas_export');
    }
}
