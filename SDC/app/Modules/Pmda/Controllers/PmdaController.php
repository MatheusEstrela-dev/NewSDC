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
use App\Modules\Pmda\Requests\ArquivarPmdaPlanoRequest;
use App\Modules\Pmda\Requests\PedirAlteracaoPmdaPlanoRequest;
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
use App\Modules\Pmda\Support\PerfilPmda;
use App\Support\Concurrency\Concurrency;
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

/**
 * Central de Analises CEDEC: tela dividida com a fila de PMDA em analise (esquerda)
 * e a fila de solicitacoes de comunidade (direita). As acoes de comunidade continuam
 * em ComunidadeSolicitacaoController; aqui ficam a listagem combinada e as decisoes de plano.
 */
class PmdaAnaliseController extends Controller
{
    public function __construct(
        private readonly PmdaPlanoService $planos,
        private readonly ComunidadeSolicitacaoService $solicitacoes,
    ) {}

    public function index(Request $request): Response
    {
        // Mesmo recorte do indice de planos: um COMPDEC com pmda.analise.view
        // ve a fila do proprio municipio, nao a do estado.
        $perfil = PerfilPmda::deUsuario($request->user());
        $filtros = $perfil->aplicarEscopo($request->only(['municipio_id']));
        // Cada painel pagina de forma independente ('page' segue aceito como
        // fallback do painel de analises por compatibilidade de URLs antigas).
        $pageAnalises = max(1, (int) $request->query('analises_page', $request->query('page', '1')));
        $pageSolicitacoes = max(1, (int) $request->query('solicitacoes_page', '1'));
        $path = $request->url();

        // Os 3 blocos sao independentes e rodam em paralelo nos task workers
        // (sequencial no fallback). Pagina e path sao capturados aqui porque o
        // task worker nao tem a request; withPath() reaplica o path ao voltar.
        $partes = Concurrency::tasks([
            'analises'     => static fn () => app(PmdaPlanoService::class)->pendentesAnalise($filtros, 15, $pageAnalises),
            'solicitacoes' => static fn () => app(ComunidadeSolicitacaoService::class)->pendentes($filtros, 15, $pageSolicitacoes),
            'municipios'   => static fn () => \App\Models\Municipio::catalogo(),
        ]);

        return Inertia::render('Pmda/Analises/Index', [
            'analises'     => PmdaPlanoListResource::collection($partes['analises']->withPath($path)),
            'solicitacoes' => ComunidadeSolicitacaoResource::collection($partes['solicitacoes']->withPath($path)),
            'filtros'      => $filtros,
            'municipios'   => $partes['municipios'],
            'perfil'       => ['e_compdec' => $perfil->eCompdec(), 'e_cedec' => $perfil->eCedec()],
        ]);
    }

    public function aprovar(Request $request, PmdaPlano $plano): RedirectResponse
    {
        try {
            $this->planos->aprovar($plano, (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['analise' => $e->getMessage()]);
        }

        return back()->with('success', 'PMDA aprovado.');
    }

    public function arquivar(ArquivarPmdaPlanoRequest $request, PmdaPlano $plano): RedirectResponse
    {
        try {
            $this->planos->arquivar($plano, $request->validated('motivo'), (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['analise' => $e->getMessage()]);
        }

        return back()->with('success', 'PMDA arquivado.');
    }

    public function pedirAlteracao(PedirAlteracaoPmdaPlanoRequest $request, PmdaPlano $plano): RedirectResponse
    {
        try {
            $this->planos->pedirAlteracao($plano, $request->validated('motivo'), (int) $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['analise' => $e->getMessage()]);
        }

        return back()->with('success', 'PMDA devolvido ao município para alteração.');
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
        $perfil = PerfilPmda::deUsuario($request->user());
        $municipioId = $perfil->municipioId();
        $filtros = $perfil->aplicarEscopo($request->only(['buscar', 'status', 'municipio_id', 'data_inicio', 'data_fim']));
        $page = max(1, (int) $request->query('page', '1'));
        $path = $request->url();

        // Listagem, statistics e municipios sao independentes: paralelos nos
        // task workers, sequenciais no fallback. statusOpcoes fica fora (enum
        // em memoria, sem query).
        //
        // O recorte de perfil e resolvido ANTES e entra como escalar: o task
        // worker roda em outro processo e nao tem a request nem o usuario.
        $partes = Concurrency::tasks([
            'planos'     => static fn () => app(PmdaPlanoService::class)->listar($filtros, 15, $page),
            'statistics' => static fn () => app(PmdaPlanoService::class)->statisticsIndex($municipioId),
            // Perfil municipal recebe so o proprio municipio, buscado direto:
            // catalogo() e um cache de 24h dos 853 municipios, e filtra-lo nao
            // enxergaria um municipio recem-cadastrado.
            'municipios' => static fn () => $municipioId !== null
                ? \App\Models\Municipio::query()->whereKey($municipioId)->get(['id', 'nome', 'uf'])
                    ->map(static fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf])->values()
                : \App\Models\Municipio::catalogo(),
        ]);

        return Inertia::render('Pmda/Index', [
            'planos'       => PmdaPlanoListResource::collection($partes['planos']->withPath($path)),
            'filtros'      => $filtros,
            'statistics'   => $partes['statistics'],
            'statusOpcoes' => collect(\App\Modules\Pmda\Enums\PmdaStatus::cases())
                ->map(fn ($s) => ['value' => $s->value, 'label' => $s->getLabel()])->values(),
            'municipios'   => $partes['municipios'],
            'perfil'       => [
                'e_compdec' => $perfil->eCompdec(),
                'e_cedec'   => $perfil->eCedec(),
                // Unica fonte do botao "Novo PMDA" no front. Espelha a
                // PmdaPlanoPolicy::create para o Vue nao reimplementar a regra.
                'pode_criar' => $request->user()?->can('create', \App\Modules\Pmda\Models\PmdaPlano::class) ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        // O municipio vem do login, nunca da URL: e a regra do legado
        // (PmdaController::create gravava em Auth::user()->municipio_id).
        // Null aqui so acontece com super-admin, que a policy deixa passar sem
        // ter orgao municipal -- nao ha onde gravar o PMDA.
        $municipioId = PerfilPmda::deUsuario($request->user())->municipioId();

        if ($municipioId === null) {
            return to_route('pmda.planos.index')->with(
                'error',
                'Seu usuário não está vinculado a um município. Só a COMPDEC do município abre PMDA.',
            );
        }

        $municipio = \App\Models\Municipio::find($municipioId);

        if ($municipio === null) {
            return to_route('pmda.planos.index')->with('error', 'Município do seu órgão não encontrado.');
        }

        $pendente = \App\Modules\Pmda\Models\PmdaPlano::query()
            ->where('municipio_id', $municipioId)
            ->whereIn('status', PmdaPlanoService::statusPendente())
            ->first();

        if ($pendente !== null) {
            // Regra do legado: "nao e possivel criar este PMDA pois ja existe
            // processo em EDICAO". Vai por flash, nao por withErrors: o indice
            // nao renderiza error bag, e sem o modal de municipio o usuario
            // ficaria com um botao que aparenta nao fazer nada.
            return to_route('pmda.planos.index')->with(
                'error',
                'Este município já possui um PMDA em aberto ('.$pendente->status->getLabel().
                    ', protocolo '.($pendente->protocolo ?? '—').'). Conclua ou exclua o existente antes de abrir outro.',
            );
        }

        return Inertia::render('Pmda/Create', [
            'municipio' => ['id' => $municipio->id, 'nome' => $municipio->nome, 'uf' => $municipio->uf],
            // Fallback: pre-preenche a aba ISS com os dados do ultimo PMDA do municipio.
            'iss_fallback' => $this->issFallback($municipioId),
        ]);
    }

    /** Dados de ISS/prefeitura do ultimo PMDA do municipio, para pre-preencher um novo. */
    private function issFallback(int $municipioId): ?array
    {
        $ultimo = \App\Modules\Pmda\Models\PmdaPlano::query()
            ->where('municipio_id', $municipioId)
            ->whereNotNull('nome_prefeito')
            ->latest('id')
            ->first();

        if ($ultimo === null) {
            return null;
        }

        return [
            'cobra_iss'        => (bool) $ultimo->cobra_iss,
            'num_lei_iss'      => $ultimo->num_lei_iss,
            'aliquota_iss'     => $ultimo->aliquota_iss,
            'resp_cob_iss'     => $ultimo->resp_cob_iss,
            'nome_prefeito'    => $ultimo->nome_prefeito,
            'tel_prefeitura'   => $ultimo->tel_prefeitura,
            'tel_prefeito'     => $ultimo->tel_prefeito,
            'cel_prefeito'     => $ultimo->cel_prefeito,
            'endereco'         => $ultimo->endereco,
            'bairro'           => $ultimo->bairro,
            'cep'              => $ultimo->cep,
            'email_prefeitura' => $ultimo->email_prefeitura,
            'populacao'        => $ultimo->populacao,
            'pop_rural'        => $ultimo->pop_rural,
            'area'             => $ultimo->area,
        ];
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->service->exportar(
            PerfilPmda::deUsuario($request->user())->aplicarEscopo($request->only(['municipio_id', 'status'])),
        );
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

    public function store(StorePmdaPlanoRequest $request): RedirectResponse
    {
        try {
            $plano = $this->service->criar(
                municipioId: (int) $request->validated('municipio_id'),
                userId: (int) $request->user()->id,
                data: collect($request->validated())->except('municipio_id')->toArray(),
            );
        } catch (\DomainException $e) {
            return to_route('pmda.planos.index')->with('error', $e->getMessage());
        }

        // Redirect Inertia (SPA, via XHR) para a continuacao: mantem o contexto de
        // CRIACAO (componente Pmda/Create, URL /continuar, breadcrumb "Novo") e atualiza
        // os props reativamente com o plano ja persistido (plano_id/protocolo). Nao usar
        // Inertia::location aqui: ele faz full reload e causa "piscada" na tela.
        return to_route('pmda.planos.continuar', ['plano' => $plano->id])
            ->with('success', 'PMDA iniciado. Continue o preenchimento.');
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
            'municipio'         => $plano->municipio
                ? ['id' => $plano->municipio->id, 'nome' => $plano->municipio->nome, 'uf' => $plano->municipio->uf]
                : null,
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
            // ->resolve() desembrulha o {data: [...]} do resource collection: o
            // modal de solicitacao espera uma lista crua (ver compdec_anexos).
            'comunidade_solicitacoes' => ComunidadeSolicitacaoResource::collection(
                $this->solicitacoes->historicoDoMunicipio((int) $plano->municipio_id)
            )->resolve(),
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

    public function destroy(Request $request, PmdaPlano $plano): RedirectResponse
    {
        // admin/super-admin excluem qualquer status; demais cargos (CEDEC) so ATENDIDO.
        $bypass = (bool) $request->user()?->hasAnyRole('super-admin', 'admin');

        try {
            $this->service->excluir($plano, $bypass);
        } catch (\DomainException $e) {
            return back()->withErrors(['plano' => $e->getMessage()]);
        }

        return to_route('pmda.planos.index')->with('success', 'PMDA excluído.');
    }

    /**
     * Dados consolidados da ficha COMPDEC para impressao (modelo BasePrintModal):
     * coordenador/orgao, equipe (ativos + anteriores) e anexos de leis/decretos.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ficha(PmdaPlano $plano): \Illuminate\Http\JsonResponse
    {
        $plano->loadMissing('municipio');
        $equipe = $this->compdecEquipeDoPlano($plano);

        return response()->json([
            'protocolo'      => $plano->protocolo,
            'municipio'      => $plano->municipio?->nome,
            'uf'             => $plano->municipio?->uf,
            'data'           => $plano->data?->toIso8601String(),
            'ficha'          => $this->compdecFicha->fichaDoPlano($plano),
            'equipe_ativos'  => array_values(array_filter($equipe, fn ($m) => $m['ativo'] === true)),
            'equipe_anteriores' => array_values(array_filter($equipe, fn ($m) => $m['ativo'] === false)),
            'anexos'         => $this->compdecAnexosDoPlano($plano),
        ]);
    }

    /**
     * Serie historica (situacao geral) do PMDA no estilo PAE: timeline + analises
     * a partir dos marcos do ciclo de vida do plano (criacao, edicoes, analise, aprovacao).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function historico(PmdaPlano $plano): \Illuminate\Http\JsonResponse
    {
        $plano->loadMissing('municipio');
        $nomePor = fn (?int $id) => $id ? (\App\Models\User::find($id)?->name ?? '—') : 'Sistema';
        $fmt = fn ($d) => $d?->format('d/m/Y, H:i');

        $timeline = [];
        $timeline[] = [
            'id' => 'criacao',
            'tipo' => 'criacao',
            'titulo' => 'Protocolo Criado',
            'descricao' => 'PMDA criado no sistema SDC.',
            'data' => $fmt($plano->created_at),
            'responsavel' => $nomePor($plano->created_by),
        ];

        // "Ultima Atualizacao" so quando for edicao real de conteudo — nao quando o
        // dt_ultima_alteracao apenas acompanha uma acao de status (enviar/aprovar/
        // arquivar/devolver), que ja tem evento proprio abaixo.
        $tsAcoes = array_filter([
            $plano->dt_analise?->getTimestamp(),
            $plano->data_aprov?->getTimestamp(),
            $plano->dt_estado?->getTimestamp(),
        ]);
        if ($plano->dt_ultima_alteracao && $plano->created_at
            && $plano->dt_ultima_alteracao->gt($plano->created_at)
            && ! in_array($plano->dt_ultima_alteracao->getTimestamp(), $tsAcoes, true)) {
            $timeline[] = [
                'id' => 'edicao',
                'tipo' => 'edicao',
                'titulo' => 'Última Atualização',
                'descricao' => 'Dados do PMDA atualizados.',
                'data' => $fmt($plano->dt_ultima_alteracao),
                'responsavel' => $nomePor($plano->updated_by),
            ];
        }

        $analises = [];
        if ($plano->dt_analise) {
            $ev = [
                'id' => 'analise',
                'tipo' => 'analise',
                'titulo' => 'Enviado para Análise',
                'descricao' => 'PMDA encaminhado para análise da CEDEC-MG.',
                'data' => $fmt($plano->dt_analise),
                'responsavel' => $plano->resp_homolog ?: '—',
            ];
            $timeline[] = $ev;
            $analises[] = $ev;
        }
        if ($plano->data_aprov) {
            $ev = [
                'id' => 'aprovacao',
                'tipo' => 'analise',
                'titulo' => 'PMDA Aprovado',
                'descricao' => 'Plano aprovado pela CEDEC-MG.',
                'data' => $fmt($plano->data_aprov),
                'responsavel' => $plano->resp_estado ?: '—',
            ];
            $timeline[] = $ev;
            $analises[] = $ev;
        }
        if ($plano->status === \App\Modules\Pmda\Enums\PmdaStatus::ARQUIVADO) {
            $ev = [
                'id' => 'arquivamento',
                'tipo' => 'notificacao',
                'titulo' => 'PMDA Arquivado',
                'descricao' => $plano->motivo_analise
                    ? ('Arquivado pela CEDEC-MG. Motivo: '.$plano->motivo_analise)
                    : 'Plano arquivado pela CEDEC-MG.',
                'data' => $fmt($plano->dt_estado),
                'responsavel' => $plano->resp_estado ?: '—',
            ];
            $timeline[] = $ev;
            $analises[] = $ev;
        }
        // Devolutiva: plano devolvido ao municipio para ajustes (nao aprovado, nao arquivado).
        if ($plano->pedido_altera && $plano->motivo_analise
            && ! $plano->data_aprov
            && $plano->status !== \App\Modules\Pmda\Enums\PmdaStatus::ARQUIVADO) {
            $ev = [
                'id' => 'pedido_alteracao',
                'tipo' => 'notificacao',
                'titulo' => 'Devolvido para Alteração',
                'descricao' => 'CEDEC-MG devolveu o PMDA ao município para ajustes. Motivo: '.$plano->motivo_analise,
                'data' => $fmt($plano->dt_estado ?? $plano->dt_ultima_alteracao),
                'responsavel' => $plano->resp_estado ?: 'CEDEC-MG',
            ];
            $timeline[] = $ev;
            $analises[] = $ev;
        }

        return response()->json([
            'protocolo'     => $plano->protocolo,
            'municipio'     => $plano->municipio?->nome,
            'status'        => $plano->status->getLabel(),
            'timeline'      => $timeline,
            'analises'      => $analises,
            'notificacoes'  => [],
        ]);
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
