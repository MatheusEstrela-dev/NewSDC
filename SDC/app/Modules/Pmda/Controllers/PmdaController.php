<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\DTOs\AnexoDTO;
use App\Modules\Compdec\DTOs\EquipeDTO;
use App\Modules\Compdec\Enums\FuncaoEquipe;
use App\Modules\Compdec\Models\CompdecAnexo;
use App\Modules\Compdec\Models\CompdecEquipe;
use App\Modules\Compdec\Resources\AnexoIndexResource;
use App\Modules\Compdec\Services\AnexoService;
use App\Modules\Compdec\Services\EquipeService;
use App\Modules\Pmda\Models\ComunidadeSolicitacao;
use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaCompdecMembro;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Models\PmdaRepresentante;
use App\Modules\Pmda\Requests\RejeitarComunidadeSolicitacaoRequest;
use App\Modules\Pmda\Requests\StoreComunidadeRequest;
use App\Modules\Pmda\Requests\StoreComunidadeSolicitacaoRequest;
use App\Modules\Pmda\Requests\StoreCompdecAnexoRequest;
use App\Modules\Pmda\Requests\StoreCompdecEquipeRequest;
use App\Modules\Pmda\Requests\UpdateCompdecFichaRequest;
use App\Modules\Pmda\Requests\StorePmdaPlanoRequest;
use App\Modules\Pmda\Requests\StoreRepresentanteRequest;
use App\Modules\Pmda\Requests\UpdatePmdaPlanoRequest;
use App\Modules\Pmda\Requests\UpdateRepresentanteRequest;
use App\Modules\Pmda\Resources\ComunidadeSolicitacaoResource;
use App\Modules\Pmda\Resources\PmdaPlanoListResource;
use App\Modules\Pmda\Resources\PmdaPlanoResource;
use App\Modules\Pmda\Services\CompdecFichaService;
use App\Modules\Pmda\Services\CompdecMembroService;
use App\Modules\Pmda\Services\ComunidadeService;
use App\Modules\Pmda\Services\ComunidadeSolicitacaoService;
use App\Modules\Pmda\Services\PlanoPontoService;
use App\Modules\Pmda\Services\PmdaCopiaService;
use App\Modules\Pmda\Services\PmdaPlanoService;
use App\Modules\Pmda\Services\RepresentanteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ComunidadeController extends Controller
{
    public function __construct(private readonly ComunidadeService $service) {}

    public function store(StoreComunidadeRequest $request, PmdaPlano $plano): RedirectResponse
    {
        $this->service->adicionar($plano, $request->validated());

        return back()->with('success', 'Comunidade adicionada.');
    }

    public function destroy(PmdaComunidade $comunidade): RedirectResponse
    {
        $this->service->remover($comunidade);

        return back()->with('success', 'Comunidade removida.');
    }
}

class ComunidadeSolicitacaoController extends Controller
{
    public function __construct(private readonly ComunidadeSolicitacaoService $service) {}

    /** Municipio: solicita o cadastro de uma comunidade ainda inexistente. */
    public function store(StoreComunidadeSolicitacaoRequest $request, PmdaPlano $plano): RedirectResponse
    {
        try {
            $this->service->criar($plano, $request->validated(), (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['solicitacao' => $e->getMessage()]);
        }

        return back()->with('success', 'Solicitação enviada para análise da CEDEC.');
    }

    /** CEDEC: fila de solicitacoes pendentes. */
    public function index(Request $request): Response
    {
        $filtros = $request->only(['municipio_id']);

        return Inertia::render('Pmda/Solicitacoes/Index', [
            'solicitacoes' => ComunidadeSolicitacaoResource::collection($this->service->pendentes($filtros)),
            'filtros'      => $filtros,
            'municipios'   => \App\Models\Municipio::query()
                ->orderBy('nome')->get(['id', 'nome', 'uf'])
                ->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf]),
        ]);
    }

    /** CEDEC: aprova e promove para o registro mestre de comunidades. */
    public function aprovar(Request $request, ComunidadeSolicitacao $solicitacao): RedirectResponse
    {
        try {
            $this->service->aprovar($solicitacao, (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['solicitacao' => $e->getMessage()]);
        }

        return back()->with('success', 'Comunidade aprovada e disponível para os PMDA do município.');
    }

    /** CEDEC: rejeita com motivo. */
    public function rejeitar(RejeitarComunidadeSolicitacaoRequest $request, ComunidadeSolicitacao $solicitacao): RedirectResponse
    {
        try {
            $this->service->rejeitar($solicitacao, $request->validated('motivo'), (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['solicitacao' => $e->getMessage()]);
        }

        return back()->with('success', 'Solicitação rejeitada.');
    }
}

class PlanoPontoController extends Controller
{
    public function __construct(private readonly PlanoPontoService $service) {}

    public function store(Request $request, PmdaPlano $plano): RedirectResponse
    {
        // Aceita vincular um ponto existente (ponto_id) OU criar um novo inline (nome + tipo).
        if ($request->filled('ponto_id')) {
            $validated = $request->validate([
                'ponto_id' => ['required', 'integer', 'exists:pip_pmda_ponto,id'],
                'situacao' => ['nullable', 'in:ATIVO,SECO'],
            ]);
            $this->service->vincular($plano, (int) $validated['ponto_id'], $validated['situacao'] ?? 'ATIVO');

            return back()->with('success', 'Ponto de captação vinculado.');
        }

        $validated = $request->validate([
            'nome'     => ['required', 'string', 'max:150'],
            'tipo'     => ['required', 'integer', 'between:1,6'],
            'situacao' => ['nullable', 'in:ATIVO,SECO'],
        ]);
        $this->service->criarEVincular($plano, $validated);

        return back()->with('success', 'Ponto de captação adicionado.');
    }

    public function destroy(PmdaPlano $plano, int $ponto): RedirectResponse
    {
        $this->service->desvincular($plano, $ponto);

        return back()->with('success', 'Ponto de captação desvinculado.');
    }
}

class PmdaPlanoController extends Controller
{
    public function __construct(
        private readonly PmdaPlanoService $service,
        private readonly PmdaCopiaService $copia,
        private readonly PlanoPontoService $pontos,
        private readonly ComunidadeService $comunidades,
        private readonly ComunidadeSolicitacaoService $solicitacoes,
        private readonly CompdecFichaService $compdecFicha,
        private readonly AnexoService $compdecAnexos,
        private readonly EquipeService $compdecEquipes,
    ) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(['buscar', 'status', 'municipio_id', 'data_inicio', 'data_fim']);

        return Inertia::render('Pmda/Index', [
            'planos'  => PmdaPlanoListResource::collection($this->service->listar($filtros)),
            'filtros'      => $filtros,
            'statistics'   => [
                'total'     => \App\Modules\Pmda\Models\PmdaPlano::count(),
                'emEdicao'  => \App\Modules\Pmda\Models\PmdaPlano::where('status', \App\Modules\Pmda\Enums\PmdaStatus::RASCUNHO->value)->count(),
                'emAnalise' => \App\Modules\Pmda\Models\PmdaPlano::where('status', \App\Modules\Pmda\Enums\PmdaStatus::EM_ANALISE->value)->count(),
                'aprovados' => \App\Modules\Pmda\Models\PmdaPlano::where('status', \App\Modules\Pmda\Enums\PmdaStatus::APROVADO->value)->count(),
            ],
            'statusOpcoes' => collect(\App\Modules\Pmda\Enums\PmdaStatus::cases())
                ->map(fn ($s) => ['value' => $s->value, 'label' => $s->getLabel()])->values(),
            'municipios' => \App\Models\Municipio::query()
                ->orderBy('nome')->get(['id', 'nome', 'uf'])
                ->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf]),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $municipioId = (int) $request->query('municipio_id');
        $municipio = \App\Models\Municipio::find($municipioId);

        if ($municipio === null) {
            return to_route('pmda.planos.index')->withErrors(['municipio_id' => 'Selecione um município válido.']);
        }

        $pendente = \App\Modules\Pmda\Models\PmdaPlano::query()
            ->where('municipio_id', $municipioId)
            ->whereIn('status', PmdaPlanoService::statusPendente())
            ->first();

        if ($pendente !== null) {
            return to_route('pmda.planos.index')->withErrors([
                'municipio_id' => 'Este município já possui um PMDA em aberto ('.$pendente->status->getLabel().
                    ', protocolo '.($pendente->protocolo ?? '—').').',
            ]);
        }

        return Inertia::render('Pmda/Create', [
            'municipio' => ['id' => $municipio->id, 'nome' => $municipio->nome, 'uf' => $municipio->uf],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->service->exportar($request->only(['municipio_id', 'status']));
        $filename = 'pmda_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            if (! empty($data)) {
                fputcsv($handle, array_keys($data[0]), ';');
            }
            foreach ($data as $row) {
                fputcsv($handle, array_values($row), ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(StorePmdaPlanoRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $plano = $this->service->criar(
                municipioId: (int) $request->validated('municipio_id'),
                userId: (int) $request->user()->id,
                data: collect($request->validated())->except('municipio_id')->toArray(),
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['municipio_id' => $e->getMessage()]);
        }

        // Redirect HARD (full reload) para a continuacao: o componente Pmda/Create
        // e reutilizado entre /create e /continuar; sem remount os props (plano_id)
        // ficavam defasados e o "Salvar e Avancar" chamava store de novo. O
        // Inertia::location forca um remount limpo com o plano ja persistido.
        // Mantem o contexto de CRIACAO (componente Pmda/Create, URL /continuar).
        session()->flash('success', 'PMDA iniciado. Continue o preenchimento.');

        return Inertia::location(route('pmda.planos.continuar', ['plano' => $plano->id]));
    }

    /**
     * Props compartilhados pelo wizard (Create-continuacao e Edit): plano + listas
     * de apoio (pontos/comunidades disponiveis, historico de solicitacoes, ficha COMPDEC).
     *
     * @return array<string, mixed>
     */
    private function wizardProps(PmdaPlano $plano): array
    {
        $plano->load(['municipio', 'comunidades.representantes', 'pontos', 'compdecMembros', 'media'])
            ->loadCount('comunidades');

        return [
            'plano'             => new PmdaPlanoResource($plano),
            'plano_id'          => $plano->id, // escalar confiavel p/ o front decidir create x update
            'pontos_disponiveis' => $this->pontos->disponiveis($plano)->map(fn ($p) => [
                'id'         => $p->id,
                'nome'       => $p->nome,
                'capacidade' => $p->capacidade,
            ])->values(),
            'comunidades_disponiveis' => $this->comunidades->disponiveis($plano)->map(fn ($c) => [
                'id'        => $c->id,
                'nome'      => $c->nome,
                'latitude'  => $c->latitude,
                'longitude' => $c->longitude,
            ])->values(),
            'comunidade_solicitacoes' => ComunidadeSolicitacaoResource::collection(
                $this->solicitacoes->historicoDoMunicipio((int) $plano->municipio_id)
            ),
            'compdec_ficha' => $this->compdecFicha->fichaDoPlano($plano),
            'compdec_anexos' => $this->compdecAnexosDoPlano($plano),
            'compdec_equipe' => $this->compdecEquipeDoPlano($plano),
        ];
    }

    /** Equipe (ativos + inativos/anteriores) do orgao COMPDEC do municipio do plano. */
    private function compdecEquipeDoPlano(PmdaPlano $plano): array
    {
        $orgao = $this->compdecFicha->orgaoDoMunicipio($plano);
        if ($orgao === null) {
            return [];
        }

        return $this->compdecEquipes->listarPorOrgao($orgao->id, 200)
            ->getCollection()
            ->map(fn (CompdecEquipe $m) => [
                'id'           => $m->id,
                'nome'         => $m->nome,
                'funcao'       => $m->funcao instanceof FuncaoEquipe ? $m->funcao->value : $m->funcao,
                'funcao_label' => $m->funcao instanceof FuncaoEquipe ? $m->funcao->label() : (string) $m->funcao,
                'cpf'          => $m->cpf,
                'telefone'     => $m->telefone,
                'celular'      => $m->celular,
                'email'        => $m->email,
                'ativo'        => (bool) $m->ativo,
            ])->values()->all();
    }

    /** Lista de documentos (anexos) do orgao COMPDEC do municipio do plano. */
    private function compdecAnexosDoPlano(PmdaPlano $plano): array
    {
        $orgao = $this->compdecFicha->orgaoDoMunicipio($plano);
        if ($orgao === null) {
            return [];
        }

        return AnexoIndexResource::collection(
            $this->compdecAnexos->listarPorOrgao($orgao->id, 100)
        )->resolve();
    }

    /**
     * Continuacao da CRIACAO apos o 1o POST: renderiza o mesmo componente Pmda/Create
     * (breadcrumb "Novo", URL de criacao) com o plano ja persistido para as abas-filhas.
     */
    public function continuar(PmdaPlano $plano): Response
    {
        return Inertia::render('Pmda/Create', $this->wizardProps($plano));
    }

    public function edit(PmdaPlano $plano): Response
    {
        return Inertia::render('Pmda/Edit', $this->wizardProps($plano));
    }

    public function update(UpdatePmdaPlanoRequest $request, PmdaPlano $plano): RedirectResponse
    {
        $this->service->atualizar($plano, $request->validated(), (int) $request->user()->id);

        return back()->with('success', 'PMDA atualizado.');
    }

    public function copiar(Request $request, PmdaPlano $plano): RedirectResponse
    {
        try {
            $copia = $this->copia->copiar($plano, (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['copiar' => $e->getMessage()]);
        }

        return to_route('pmda.planos.edit', $copia->id)->with('success', 'Cópia criada.');
    }

    /** Etapa 7: upload de anexo (Termo de Compromisso ou Ofício) em PDF. */
    public function storeAnexo(Request $request, PmdaPlano $plano): RedirectResponse
    {
        $validated = $request->validate([
            'colecao' => ['required', 'in:termo,oficio'],
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $plano->addMediaFromRequest('arquivo')->toMediaCollection($validated['colecao']);

        return back()->with('success', 'Anexo enviado.');
    }

    /** Etapa 7: envia o PMDA para análise da CEDEC (transição para EM_ANALISE). */
    public function enviar(Request $request, PmdaPlano $plano): RedirectResponse
    {
        try {
            $this->service->enviar($plano, (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['enviar' => $e->getMessage()]);
        }

        return to_route('pmda.planos.index')->with('success', 'PMDA enviado para análise da CEDEC.');
    }
}

class RepresentanteController extends Controller
{
    public function __construct(private readonly RepresentanteService $service) {}

    public function store(StoreRepresentanteRequest $request, PmdaComunidade $comunidade): RedirectResponse
    {
        $this->service->adicionar($comunidade, $request->validated());

        return back()->with('success', 'Representante adicionado.');
    }

    public function update(UpdateRepresentanteRequest $request, PmdaRepresentante $representante): RedirectResponse
    {
        $this->service->atualizar($representante, $request->validated());

        return back()->with('success', 'Representante atualizado.');
    }

    public function destroy(PmdaRepresentante $representante): RedirectResponse
    {
        $this->service->remover($representante);

        return back()->with('success', 'Representante removido.');
    }
}

class CompdecMembroController extends Controller
{
    public function __construct(private readonly CompdecMembroService $service) {}

    public function store(Request $request, PmdaPlano $plano): RedirectResponse
    {
        $data = $request->validate([
            'nome'     => ['required', 'string', 'max:110'],
            'cargo'    => ['nullable', 'string', 'max:80'],
            'telefone' => ['nullable', 'string', 'max:20'],
        ]);

        $this->service->adicionar($plano, $data);

        return back()->with('success', 'Membro adicionado.');
    }

    public function destroy(PmdaCompdecMembro $membro): RedirectResponse
    {
        $this->service->remover($membro);

        return back()->with('success', 'Membro removido.');
    }
}

class CompdecFichaController extends Controller
{
    public function __construct(private readonly CompdecFichaService $service) {}

    /** Grava a ficha cadastral do COMPDEC (registro mestre do municipio do plano). */
    public function update(UpdateCompdecFichaRequest $request, PmdaPlano $plano): RedirectResponse
    {
        $this->service->salvar($plano, $request->validated());

        return back()->with('success', 'Ficha do COMPDEC atualizada.');
    }

    /** Foto do coordenador do COMPDEC (upload/alterar). */
    public function uploadFoto(Request $request, PmdaPlano $plano): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $this->service->uploadFoto($plano, $request->file('foto'));

        return back()->with('success', 'Foto atualizada.');
    }

    public function removerFoto(PmdaPlano $plano): RedirectResponse
    {
        $this->service->removerFoto($plano);

        return back()->with('success', 'Foto removida.');
    }
}

/**
 * Documentos (Leis e Decretos) do COMPDEC a partir do PMDA. Reusa o AnexoService
 * do modulo Compdec, gravando nos anexos do orgao COMPDEC do municipio do plano.
 */
class CompdecAnexoController extends Controller
{
    public function __construct(
        private readonly AnexoService $anexos,
        private readonly CompdecFichaService $ficha,
    ) {}

    public function store(StoreCompdecAnexoRequest $request, PmdaPlano $plano): RedirectResponse
    {
        $orgao = $this->ficha->garantirOrgao($plano);

        $this->anexos->criar(
            $orgao->id,
            AnexoDTO::fromRequest($request->validated()),
            $request->file('arquivo'),
        );

        return back()->with('success', 'Documento anexado.');
    }

    public function destroy(PmdaPlano $plano, CompdecAnexo $anexo): RedirectResponse
    {
        // Garante que o anexo pertence ao orgao COMPDEC do municipio do plano.
        $orgao = $this->ficha->orgaoDoMunicipio($plano);
        abort_unless($orgao !== null && $anexo->orgao_id === $orgao->id, 404);

        $this->anexos->deletar($orgao->id, $anexo->id);

        return back()->with('success', 'Documento removido.');
    }

    public function download(PmdaPlano $plano, CompdecAnexo $anexo)
    {
        $orgao = $this->ficha->orgaoDoMunicipio($plano);
        abort_unless($orgao !== null && $anexo->orgao_id === $orgao->id, 404);

        return $this->anexos->download($orgao->id, $anexo->id);
    }
}

/**
 * Equipe COMPDEC (Editar Equipe) a partir do PMDA. Reusa o EquipeService do
 * modulo Compdec, gravando nos membros do orgao COMPDEC do municipio do plano.
 */
class CompdecEquipeController extends Controller
{
    public function __construct(
        private readonly EquipeService $equipes,
        private readonly CompdecFichaService $ficha,
    ) {}

    public function store(StoreCompdecEquipeRequest $request, PmdaPlano $plano): RedirectResponse
    {
        $orgao = $this->ficha->garantirOrgao($plano);

        try {
            $this->equipes->criar($orgao->id, EquipeDTO::fromRequest($request->validated()));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['equipe' => $e->getMessage()]);
        }

        return back()->with('success', 'Membro da equipe adicionado.');
    }

    public function update(StoreCompdecEquipeRequest $request, PmdaPlano $plano, CompdecEquipe $equipe): RedirectResponse
    {
        $orgao = $this->ficha->orgaoDoMunicipio($plano);
        abort_unless($orgao !== null && $equipe->orgao_id === $orgao->id, 404);

        try {
            $this->equipes->atualizar($orgao->id, $equipe->id, EquipeDTO::fromRequest($request->validated()));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['equipe' => $e->getMessage()]);
        }

        return back()->with('success', 'Membro da equipe atualizado.');
    }

    public function destroy(PmdaPlano $plano, CompdecEquipe $equipe): RedirectResponse
    {
        $orgao = $this->ficha->orgaoDoMunicipio($plano);
        abort_unless($orgao !== null && $equipe->orgao_id === $orgao->id, 404);

        $this->equipes->deletar($orgao->id, $equipe->id);

        return back()->with('success', 'Membro da equipe removido.');
    }
}
