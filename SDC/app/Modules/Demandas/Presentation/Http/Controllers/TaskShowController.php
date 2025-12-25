<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Visualizar e Gerenciar Demanda
 */
class TaskShowController extends Controller
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {
    }

    /**
     * Exibir detalhes da demanda
     */
    public function show(Request $request, int $id): Response
    {
        $task = $this->taskRepository->find($id);

        if (! $task) {
            abort(404, 'Demanda não encontrada');
        }

        // Verificar se pode ver esta demanda
        if (! $request->user()->can('demandas.manage') && $task->solicitante_id !== $request->user()->id) {
            abort(403, 'Você não tem permissão para ver esta demanda');
        }

        $task->load(['solicitante', 'atribuidoPara', 'comments.autor', 'attachments']);

        return Inertia::render('Demandas/DemandasShow', [
            'task' => [
                'id' => $task->id,
                'protocolo' => $task->protocolo,
                'titulo' => $task->titulo,
                'descricao' => $task->descricao,
                'tipo' => $task->tipo->value,
                'tipo_label' => $task->tipo->label(),
                'status' => $task->status->value,
                'status_label' => $task->status->label(),
                'status_color' => $task->status->color(),
                'prioridade' => $task->prioridade,
                'prioridade_label' => $task->prioridade->label(),
                'prioridade_color' => $task->prioridade->color(),
                'urgencia' => $task->urgencia?->value,
                'urgencia_label' => $task->urgencia?->label() ?? 'Não informada',
                'impacto' => $task->impacto?->value,
                'impacto_label' => $task->impacto?->label() ?? 'Não informado',
                'categoria' => $task->categoria,
                'subcategoria' => $task->subcategoria,
                'solicitante_nome' => $task->solicitante?->name ?? 'Desconhecido',
                'atribuido_para_nome' => $task->atribuidoPara?->name,
                'criado_em_formatado' => $task->created_at->format('d/m/Y H:i'),
                'criado_em_diff' => $task->created_at->diffForHumans(),
                'atualizado_em_formatado' => $task->updated_at?->format('d/m/Y H:i'),
                'prazo_resolucao' => $task->prazo_resolucao,
                'prazo_resolucao_formatado' => $task->prazo_resolucao?->format('d/m/Y H:i'),
                'sla_resolucao_violado' => $task->sla_resolucao_violado,
                'comentarios' => $task->comments->map(function ($comentario) {
                    return [
                        'id' => $comentario->id,
                        'comentario' => $comentario->comentario,
                        'autor_nome' => $comentario->autor?->name ?? 'Sistema',
                        'autor_iniciais' => $this->getIniciais($comentario->autor?->name ?? 'S'),
                        'criado_em_diff' => $comentario->created_at->diffForHumans(),
                    ];
                }),
                'anexos' => $task->attachments->map(function ($anexo) {
                    return [
                        'id' => $anexo->id,
                        'nome_arquivo' => $anexo->nome_arquivo,
                        'url' => asset('storage/' . $anexo->caminho_arquivo),
                    ];
                }),
            ],
            'canComment' => $request->user()->can('demandas.comment-own') || $request->user()->can('demandas.manage'),
            'canManage' => $request->user()->can('demandas.manage'),
            'canEdit' => $request->user()->can('demandas.edit'),
        ]);
    }

    /**
     * Obter iniciais de um nome
     */
    private function getIniciais(string $nome): string
    {
        $parts = explode(' ', trim($nome));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($nome, 0, 2));
    }

    /**
     * Adicionar comentário
     */
    public function addComment(Request $request, int $id)
    {
        $task = $this->taskRepository->find($id);

        if (! $task) {
            abort(404, 'Demanda não encontrada');
        }

        // Verificar permissão
        $user = $request->user();
        if (! $user->can('demandas.manage') && $task->solicitante_id !== $user->id) {
            abort(403, 'Você não tem permissão para comentar nesta demanda');
        }

        $validated = $request->validate([
            'comentario' => 'required|string|max:5000',
        ]);

        $task->comments()->create([
            'comentario' => $validated['comentario'],
            'autor_id' => $user->id,
            'visivel_solicitante' => true,
        ]);

        return redirect()->back()->with('success', 'Comentário adicionado!');
    }

    /**
     * Adicionar anexo
     */
    public function addAttachment(Request $request, int $id)
    {
        // TODO: Implementar

        return redirect()->back()->with('success', 'Anexo adicionado!');
    }

    /**
     * Editar demanda (apenas agentes)
     */
    public function edit(Request $request, int $id): Response
    {
        $this->authorize('demandas.edit');

        $task = $this->taskRepository->find($id);

        if (! $task) {
            abort(404);
        }

        return Inertia::render('Admin/Demandas/DemandasEdit', [
            'task' => $task,
        ]);
    }

    /**
     * Atualizar demanda
     */
    public function update(Request $request, int $id)
    {
        $this->authorize('demandas.edit');

        // TODO: Implementar

        return redirect()->back()->with('success', 'Demanda atualizada!');
    }

    /**
     * Deletar demanda
     */
    public function destroy(int $id)
    {
        $this->authorize('demandas.delete');

        $this->taskRepository->delete($id);

        return redirect()
            ->route('admin.demandas.index')
            ->with('success', 'Demanda deletada!');
    }
}
