<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\DTOs\AtualizarDemandaData;
use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\DTOs\FiltroDemanda;
use App\Modules\Demandas\DTOs\ResolucaoDemandaData;
use App\Modules\Demandas\Enums\EtapaDemanda;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAnexo;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Models\DemandaAuditLog;
use App\Modules\Demandas\Models\DemandaComentario;
use App\Modules\Demandas\Requests\AnexoDemandaRequest;
use App\Modules\Demandas\Requests\ResolverDemandaRequest;
use App\Modules\Demandas\Requests\StoreDemandaRequest;
use App\Modules\Demandas\Requests\UpdateDemandaRequest;
use App\Modules\Demandas\Services\DemandaAnexoService;
use App\Modules\Demandas\Services\DemandaCsvExporter;
use App\Modules\Demandas\Services\DemandaInteractionService;
use App\Modules\Demandas\Services\DemandaStatusService;
use App\Modules\Demandas\Services\DemandaWriteService;
use App\Modules\Demandas\Services\ExecutarAutomacaoDemanda;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DemandaController extends Controller
{
    public function __construct(
        private readonly DemandaRepository $repository,
        private readonly DemandaWriteService $escrita,
        private readonly DemandaStatusService $status,
        private readonly DemandaInteractionService $interactions,
        private readonly DemandaAnexoService $anexos,
        private readonly DemandaCsvExporter $csvExporter,
        private readonly ExecutarAutomacaoDemanda $automacao,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Demanda::class);
        $user = $request->user();
        $gerir = $user->can('demandas.chamados.manage');
        $filtros = FiltroDemanda::fromRequest($request)->toArray();

        $demandas = $this->repository->paginate($filtros, 15, (int) $user->id, $gerir)
            ->through(fn (Demanda $d): array => $this->resumo($d));

        return Inertia::render('Demandas/DemandasIndex', [
            'demandas' => $demandas,
            'estatisticas' => $this->repository->getStatistics((int) $user->id, $gerir),
            'filtros' => $filtros,
            'opcoes' => [
                'etapas' => EtapaDemanda::options(),
                'prioridades' => PrioridadeSimples::options(),
                'assuntos' => $this->opcoesAssunto(),
                'usuarios' => $gerir ? $this->opcoesUsuario() : [],
            ],
            'pode' => [
                'criar' => $user->can('demandas.chamados.create'),
                'exportar' => $user->can('demandas.chamados.export'),
                'gerir' => $gerir,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Demanda::class);
        $gerir = $request->user()->can('demandas.chamados.manage');

        return Inertia::render('Demandas/DemandasCreate', [
            'assuntos' => DemandaAssunto::query()->where('ativo', true)->with('categoria:id,nome')->orderBy('nome')
                ->get(['id', 'nome', 'categoria_id', 'campos_dinamicos'])
                ->map(fn (DemandaAssunto $a): array => [
                    'value' => $a->id, 'label' => $a->nome,
                    'categoria' => $a->categoria?->nome, 'campos' => $a->campos_dinamicos ?? [],
                ]),
            'prioridades' => PrioridadeSimples::options(),
            'usuarios' => $gerir ? $this->opcoesUsuario() : [],
            'pode' => ['gerir' => $gerir],
        ]);
    }

    public function store(StoreDemandaRequest $request): RedirectResponse
    {
        $demanda = $this->escrita->abrir(CriarDemandaData::fromRequest($request));

        return redirect()->route('demandas.show', $demanda->id)->with('success', 'Demanda aberta: '.$demanda->protocolo);
    }

    public function show(Request $request, int $id): Response
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('view', $demanda);
        $user = $request->user();
        $gerir = $user->can('demandas.chamados.manage');
        $automacao = $demanda->assunto?->form_automacao;

        $comentarios = $demanda->comments
            ->when(! $gerir, fn ($c) => $c->where('interno', false))
            ->sortBy('created_at')->values()
            ->map(fn (DemandaComentario $c): array => [
                'id' => $c->id, 'conteudo' => $c->conteudo, 'interno' => (bool) $c->interno,
                'created_at' => $c->created_at?->toIso8601String(), 'autor' => $c->user?->name,
            ]);

        $historico = $demanda->auditLogs->sortByDesc('created_at')->values()
            ->map(fn (DemandaAuditLog $l): array => [
                'id' => $l->id, 'rotulo' => $l->metadata['rotulo'] ?? $l->acao,
                'detalhes' => $l->metadata['detalhes'] ?? null,
                'created_at' => $l->created_at?->toIso8601String(), 'autor' => $l->user?->name,
            ]);

        return Inertia::render('Demandas/DemandasShow', [
            'demanda' => array_merge($this->resumo($demanda), [
                'descricao' => $demanda->descricao,
                'campos_customizados' => $demanda->campos_customizados ?? [],
                'primeira_resposta_em' => $demanda->primeira_resposta_em?->toIso8601String(),
                'prazo_resolucao' => $demanda->prazo_resolucao?->toIso8601String(),
                'sla_resolucao_violado' => (bool) $demanda->sla_resolucao_violado,
            ]),
            'campos' => $demanda->assunto?->campos_dinamicos ?? [],
            'comentarios' => $comentarios,
            'historico' => $historico,
            'anexos' => $demanda->attachments->map(fn (DemandaAnexo $a): array => [
                'id' => $a->id, 'nome_original' => $a->nome_original, 'tamanho_bytes' => (int) $a->tamanho_bytes,
                'created_at' => $a->created_at?->toIso8601String(), 'autor' => $a->user?->name,
                'url' => route('demandas.attachments.download', [$demanda->id, $a->id]),
            ])->values(),
            'assuntos' => $this->opcoesAssunto(),
            'usuarios' => $gerir ? $this->opcoesUsuario() : [],
            'automacao' => ['disponivel' => is_array($automacao) && isset($automacao['acao']), 'acao' => $automacao['acao'] ?? null],
            'pode' => [
                'editar' => $user->can('update', $demanda),
                'gerir' => $gerir,
                'resolver' => $user->can('resolver', $demanda) && $demanda->status->isActive(),
                'reabrir' => $user->can('resolver', $demanda) && $demanda->status === StatusDemanda::RESOLVIDA,
                'automatizar' => $user->can('automatizar', $demanda),
                'comentarInterno' => $gerir,
            ],
        ]);
    }

    public function update(int $id, UpdateDemandaRequest $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->escrita->atualizar($demanda, AtualizarDemandaData::fromRequest($request), (int) $request->user()->id);

        return redirect()->back()->with('success', 'Alterações salvas.');
    }

    public function addComment(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('comment', $demanda);
        $data = $request->validate([
            'conteudo' => ['required', 'string', 'max:10000'],
            'interno' => ['sometimes', 'boolean'],
        ]);
        abort_if(($data['interno'] ?? false) && ! $request->user()->can('demandas.chamados.manage'), 403);

        try {
            $this->interactions->comentar($demanda, (int) $request->user()->id, $data['conteudo'], (bool) ($data['interno'] ?? false));
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['conteudo' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Comentário registrado.');
    }

    public function assign(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('manage', $demanda);
        $data = $request->validate(['responsavel_id' => ['required', 'integer', 'exists:users,id']]);
        $this->interactions->atribuir($demanda, (int) $data['responsavel_id'], (int) $request->user()->id);

        return redirect()->back()->with('success', 'Responsável atualizado.');
    }

    public function changeStatus(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('update', $demanda);
        $data = $request->validate(['status' => ['required', Rule::enum(StatusDemanda::class)]]);

        try {
            $this->status->alterarStatus($demanda, StatusDemanda::from($data['status']), (int) $request->user()->id);
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Status atualizado.');
    }

    public function resolver(int $id, ResolverDemandaRequest $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        try {
            $this->status->resolver($demanda, ResolucaoDemandaData::fromRequest($request), (int) $request->user()->id);
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['resolvida_em' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Chamado resolvido.');
    }

    public function reabrir(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('resolver', $demanda);
        try {
            $this->status->reabrir($demanda, (int) $request->user()->id);
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Chamado reaberto.');
    }

    public function automatizar(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('automatizar', $demanda);

        try {
            $this->automacao->solicitar($demanda, (int) $request->user()->id);
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['automacao' => $e->getMessage()]);
        } catch (\Throwable) {
            // Fila sincrona (dev/teste): o SyncQueue chama failed() do job
            // (que ja grava o automation_failed) antes de relancar a
            // excecao ate aqui; o estado da demanda nao muda. So resta
            // avisar o usuario com uma mensagem generica.
            return redirect()->back()->withErrors(['automacao' => 'O diretório corporativo não respondeu. Tente novamente.']);
        }

        return redirect()->back()->with('success', 'Automação solicitada. Acompanhe no histórico.');
    }

    public function addAttachment(int $id, AnexoDemandaRequest $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->anexos->anexar($demanda, $request->file('arquivo'), (int) $request->user()->id);

        return redirect()->back()->with('success', 'Anexo enviado.');
    }

    public function downloadAttachment(int $id, int $anexoId)
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('view', $demanda);

        return $this->anexos->baixar($demanda, DemandaAnexo::findOrFail($anexoId));
    }

    public function destroy(int $id): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('delete', $demanda);
        $this->repository->delete($demanda);

        return redirect()->route('demandas.index')->with('success', 'Demanda removida.');
    }

    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()->can('demandas.chamados.manage'), 403);

        return $this->index($request);
    }

    public function export(Request $request)
    {
        $this->authorize('export', Demanda::class);

        return $this->csvExporter->download(
            FiltroDemanda::fromRequest($request)->toArray(),
            (int) $request->user()->id,
            $request->user()->can('demandas.chamados.manage'),
        );
    }

    private function resumo(Demanda $d): array
    {
        $simples = PrioridadeSimples::dePrioridade($d->prioridade);

        return [
            'id' => $d->id,
            'protocolo' => $d->protocolo,
            'titulo' => $d->titulo,
            'status' => $d->status->value,
            'status_label' => $d->status->label(),
            'etapa' => $d->status->etapa()->value,
            'etapa_label' => $d->status->etapa()->label(),
            'prioridade_simples' => $simples->value,
            'prioridade_label' => $simples->label(),
            'prioridade_itil' => $d->prioridade?->label(),
            'created_at' => $d->created_at?->toIso8601String(),
            'resolvido_em' => $d->resolvido_em?->toIso8601String(),
            'assunto' => $d->assunto ? ['id' => $d->assunto->id, 'nome' => $d->assunto->nome, 'categoria' => $d->assunto->categoria?->nome] : null,
            'solicitante' => $d->solicitante ? ['id' => $d->solicitante->id, 'name' => $d->solicitante->name] : null,
            'atribuido_para' => $d->atribuidoPara ? ['id' => $d->atribuidoPara->id, 'name' => $d->atribuidoPara->name] : null,
            'criado_por' => $d->criadoPor ? ['id' => $d->criadoPor->id, 'name' => $d->criadoPor->name] : null,
            'legado_id' => $d->campos_customizados['_legado']['id'] ?? null,
        ];
    }

    /** @return list<array{value:int,label:string}> */
    private function opcoesAssunto(): array
    {
        return DemandaAssunto::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome'])
            ->map(fn (DemandaAssunto $a): array => ['value' => $a->id, 'label' => $a->nome])->all();
    }

    /** @return list<array{value:int,label:string}> */
    private function opcoesUsuario(): array
    {
        return User::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $u): array => ['value' => $u->id, 'label' => $u->name])->all();
    }
}
