<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\DTOs\AtualizarDemandaData;
use App\Modules\Demandas\DTOs\FiltroDemanda;
use App\Modules\Demandas\Domain\Workflows\DemandaWorkflow;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Enums\Prioridade;
use App\Modules\Demandas\Requests\StoreDemandaRequest;
use App\Modules\Demandas\Requests\UpdateDemandaRequest;
use App\Modules\Demandas\Domain\Events\DemandaCriadaV1;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaCategoria;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Services\DemandaInteractionService;
use App\Modules\Demandas\Services\DemandaCsvExporter;
use App\Modules\Demandas\Models\DemandaAnexo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DemandaController extends Controller
{
    public function __construct(
        private readonly DemandaRepository $repository,
        private readonly DemandaWorkflow $workflow,
        private readonly DemandaInteractionService $interactions,
        private readonly DemandaCsvExporter $csvExporter,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Demanda::class);
        $filters = FiltroDemanda::fromRequest($request)->toArray();
        $manage = $request->user()->can('demandas.chamados.manage');
        $viewerId = (int) $request->user()->id;
        $tasks = $this->repository->paginate($filters, 15, $viewerId, $manage);
        $statistics = $this->repository->getStatistics($viewerId, $manage);
        $categorias = DemandaCategoria::whereNull('parent_id')->with('subcategorias')->where('ativo', true)->get();
        $usuarios = $manage ? User::select('id', 'name')->where('active', true)->orderBy('name')->get() : [];

        return Inertia::render('Demandas/DemandasIndex', [
            'tasks' => $tasks,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusDemanda::toSelectArray(),
                'tipos' => TipoDemanda::toSelectArray(),
                'prioridades' => Prioridade::toSelectArray(),
                'categorias' => $categorias,
                'usuarios' => $usuarios,
            ]
        ]);
    }

    public function create()
    {
        $this->authorize('create', Demanda::class);

        return Inertia::render('Demandas/DemandasCreate', [
            'tipos' => TipoDemanda::toSelectArray(),
            'categorias' => DemandaCategoria::whereNull('parent_id')->where('ativo', true)
                ->with('subcategorias')->get()->mapWithKeys(fn (DemandaCategoria $categoria) => [
                    $categoria->nome => $categoria->subcategorias->pluck('nome')->values(),
                ]),
            'assuntos' => DemandaAssunto::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'categoria_id']),
        ]);
    }

    public function store(StoreDemandaRequest $request)
    {
        $dto = CriarDemandaData::fromRequest($request);

        DB::transaction(function () use ($dto) {
            $demanda = new Demanda($dto->toArray());
            $demanda->status = StatusDemanda::ABERTA;
            
            $this->repository->save($demanda);

            DB::afterCommit(fn () => event(DemandaCriadaV1::create(
                $demanda->id,
                $demanda->protocolo,
                $demanda->tipo->value,
                (string) ($demanda->prioridade?->value ?? 3),
                $demanda->solicitante_id
            )));
        });

        return redirect()->route('demandas.index')->with('success', 'Demanda criada com sucesso!');
    }

    public function show(int $id)
    {
        $demanda = $this->repository->findById($id);
        
        if (!$demanda) {
            abort(404, 'Demanda não encontrada.');
        }
        $this->authorize('view', $demanda);

        if (! request()->user()->can('demandas.chamados.manage')) {
            $demanda->setRelation('comments', $demanda->comments->where('interno', false)->values());
            $demanda->unsetRelation('auditLogs');
            $demanda->unsetRelation('approvals');
        }

        return Inertia::render('Demandas/DemandasShow', [
            'demanda' => $demanda,
            'assunto' => $demanda->assunto?->nome,
            'statusOptions' => collect($demanda->status->getAllowedTransitions())
                ->map(fn (StatusDemanda $status) => ['value' => $status->value, 'label' => $status->label()]),
            'usuarios' => request()->user()->can('demandas.chamados.manage')
                ? User::select('id', 'name')->where('active', true)->orderBy('name')->get() : [],
        ]);
    }

    public function update(int $id, UpdateDemandaRequest $request)
    {
        $demanda = $this->repository->findById($id);
        if (!$demanda) abort(404);
        $this->authorize('update', $demanda);

        $dto = AtualizarDemandaData::fromRequest($request);

        DB::transaction(function () use ($demanda, $dto) {
            $demanda->fill($dto->toArray());
            $this->repository->save($demanda);
        });

        return redirect()->back()->with('success', 'Demanda atualizada com sucesso!');
    }

    public function addComment(int $id, Request $request)
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('comment', $demanda);
        $data = $request->validate([
            'conteudo' => ['required', 'string', 'max:10000'],
            'interno' => ['sometimes', 'boolean'],
        ]);
        if (($data['interno'] ?? false) && ! $request->user()->can('demandas.chamados.manage')) {
            abort(403);
        }

        $this->interactions->comentar($demanda, (int) $request->user()->id, $data['conteudo'], (bool) ($data['interno'] ?? false));

        return redirect()->back()->with('success', 'Comentário registrado.');
    }

    public function assign(int $id, Request $request)
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('manage', $demanda);
        $data = $request->validate(['responsavel_id' => ['required', 'integer', 'exists:users,id']]);
        $this->interactions->atribuir($demanda, (int) $data['responsavel_id']);

        return redirect()->back()->with('success', 'Responsável atualizado.');
    }

    public function changeStatus(int $id, Request $request)
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('update', $demanda);
        $data = $request->validate(['status' => ['required', Rule::enum(StatusDemanda::class)]]);
        try {
            $this->workflow->transitar($demanda, StatusDemanda::from($data['status']), (int) $request->user()->id);
        } catch (\DomainException $exception) {
            return redirect()->back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()->back()->with('success', 'Status atualizado.');
    }

    public function addAttachment(int $id, Request $request)
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('comment', $demanda);
        $data = $request->validate(['arquivo' => ['required', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,txt']]);
        $arquivo = $data['arquivo'];
        $path = $arquivo->store('demandas/'.$demanda->id, 'local');
        abort_if($path === false, 500, 'Falha ao armazenar o anexo.');

        try {
            $demanda->attachments()->create([
                'user_id' => $request->user()->id,
                'nome_original' => $arquivo->getClientOriginalName(),
                'nome_arquivo' => basename($path),
                'mime_type' => $arquivo->getMimeType(),
                'tamanho_bytes' => $arquivo->getSize(),
                'path' => $path,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->back()->with('success', 'Anexo enviado.');
    }

    public function downloadAttachment(int $id, DemandaAnexo $anexo)
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('view', $demanda);
        abort_unless((int) $anexo->task_id === $demanda->id, 404);
        abort_unless(Storage::disk('local')->exists($anexo->path), 404);

        return Storage::disk('local')->download($anexo->path, $anexo->nome_original);
    }

    public function destroy(int $id)
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('delete', $demanda);
        $this->repository->delete($demanda);

        return redirect()->route('demandas.index')->with('success', 'Demanda removida.');
    }

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->can('demandas.chamados.manage'), 403);
        return $this->index($request);
    }

    public function export(Request $request)
    {
        $this->authorize('export', Demanda::class);
        $filters = FiltroDemanda::fromRequest($request)->toArray();
        return $this->csvExporter->download($filters, (int) $request->user()->id, $request->user()->can('demandas.chamados.manage'));
    }
}



