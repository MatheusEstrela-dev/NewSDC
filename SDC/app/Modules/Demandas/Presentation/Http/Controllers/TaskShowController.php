<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Application\UseCases\ShowTaskUseCase;
use App\Modules\Demandas\Application\UseCases\UpdateTaskUseCase;
use App\Modules\Demandas\Application\UseCases\DeleteTaskUseCase;
use App\Modules\Demandas\Application\UseCases\AddCommentUseCase;
use App\Modules\Demandas\Presentation\Http\Requests\UpdateTaskRequest;
use App\Modules\Demandas\Presentation\Http\Requests\AddCommentRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Visualizar e Gerenciar Demanda
 */
class TaskShowController extends Controller
{
    public function __construct(
        private readonly ShowTaskUseCase $showTaskUseCase,
        private readonly UpdateTaskUseCase $updateTaskUseCase,
        private readonly DeleteTaskUseCase $deleteTaskUseCase,
        private readonly AddCommentUseCase $addCommentUseCase,
    ) {
    }

    /**
     * Exibir detalhes da demanda
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $task = $this->showTaskUseCase->execute($id);

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
        } catch (\DomainException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            abort(500, 'Erro ao carregar detalhes da demanda.');
        }
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
    public function addComment(AddCommentRequest $request, int $id)
    {
        try {
            $task = $this->showTaskUseCase->execute($id);

            // Verificar permissão
            $user = $request->user();
            if (! $user->can('demandas.manage') && $task->solicitante_id !== $user->id) {
                abort(403, 'Você não tem permissão para comentar nesta demanda');
            }

            $validated = $request->validated();

            $this->addCommentUseCase->execute($id, $user->id, $validated['comentario']);

            return redirect()->back()->with('success', 'Comentário adicionado!');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao adicionar comentário. Por favor, tente novamente.');
        }
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
        try {
            $this->authorize('demandas.edit');

            $task = $this->showTaskUseCase->execute($id);

            return Inertia::render('Admin/Demandas/DemandasEdit', [
                'task' => $task,
            ]);
        } catch (\DomainException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            abort(500, 'Erro ao carregar formulário de edição.');
        }
    }

    /**
     * Atualizar demanda
     */
    public function update(UpdateTaskRequest $request, int $id)
    {
        try {
            $validated = $request->validated();

            $this->updateTaskUseCase->execute($id, $validated);

            return redirect()->back()->with('success', 'Demanda atualizada!');
        } catch (\DomainException $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Erro ao atualizar demanda. Por favor, tente novamente.');
        }
    }

    /**
     * Deletar demanda
     */
    public function destroy(int $id)
    {
        try {
            $this->authorize('demandas.delete');

            $this->deleteTaskUseCase->execute($id);

            return redirect()
                ->route('admin.demandas.index')
                ->with('success', 'Demanda deletada!');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao deletar demanda. Por favor, tente novamente.');
        }
    }
}
