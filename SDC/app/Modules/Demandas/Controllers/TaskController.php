<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Services\TaskService;
use App\Modules\Demandas\Enums\TaskStatus;
use App\Modules\Demandas\Enums\Prioridade;
use App\Modules\Demandas\Enums\TipoTask;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'prioridade', 'tipo']);
        $tasks = $this->taskService->list($filters, 15);
        $statistics = $this->taskService->getStatistics();

        return Inertia::render('Demandas/DemandasIndex', [
            'tasks' => $tasks,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => TaskStatus::toSelectArray(),
                'prioridades' => Prioridade::toSelectArray(),
                'tipos' => TipoTask::toSelectArray(),
            ],
        ]);
    }

    public function adminIndex(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'prioridade', 'tipo']);
        $tasks = $this->taskService->list($filters, 15);
        $statistics = $this->taskService->getStatistics();

        return Inertia::render('Demandas/DemandasIndex', [
            'tasks' => $tasks,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => TaskStatus::toSelectArray(),
                'prioridades' => Prioridade::toSelectArray(),
                'tipos' => TipoTask::toSelectArray(),
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $task = $this->taskService->findById($id);

        if (!$task) {
            abort(404, 'Tarefa nao encontrada');
        }

        return Inertia::render('Demandas/DemandasShow', [
            'task' => $task,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Demandas/DemandasCreate', [
            'filterOptions' => [
                'prioridades' => Prioridade::toSelectArray(),
                'tipos' => TipoTask::toSelectArray(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string',
            'prioridade' => 'required|string',
            'tipo' => 'required|string',
        ]);

        $task = $this->taskService->create($validated);

        return redirect()->route('demandas.show', $task->id)
            ->with('success', 'Demanda cadastrada com sucesso!');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'descricao' => 'sometimes|string',
            'status' => 'sometimes|string',
        ]);

        $this->taskService->update($id, $validated);

        return redirect()->back()->with('success', 'Demanda atualizada com sucesso!');
    }

    public function destroy(int $id)
    {
        $this->taskService->delete($id);

        return redirect()->route('demandas.index')
            ->with('success', 'Demanda removida com sucesso!');
    }

    public function assign(Request $request, int $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $this->taskService->assign($id, $validated['user_id']);

        return redirect()->back()->with('success', 'Demanda atribuida com sucesso!');
    }

    public function changeStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $this->taskService->changeStatus($id, $validated['status']);

        return redirect()->back()->with('success', 'Status atualizado com sucesso!');
    }

    public function addComment(Request $request, int $id)
    {
        $validated = $request->validate([
            'conteudo' => 'required|string',
        ]);

        $task = $this->taskService->findById($id);
        if (!$task) {
            abort(404, 'Tarefa nao encontrada');
        }

        $task->comments()->create([
            'conteudo' => $validated['conteudo'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Comentario adicionado com sucesso!');
    }

    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status', 'prioridade', 'tipo']);
        // Export logic here
        return response()->json(['message' => 'Export not implemented yet']);
    }
}
