<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\CaminhaoDTO;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Requests\StoreCaminhaoRequest;
use App\Modules\Tdap\Requests\UpdateCaminhaoRequest;
use App\Modules\Tdap\Resources\CaminhaoIndexResource;
use App\Modules\Tdap\Resources\CaminhaoResource;
use App\Modules\Tdap\Services\FrotaService;
use App\Modules\Tdap\Support\ExportadorCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FrotaController extends Controller
{
    /**
     * Allowlist de filtros da frota.
     *
     * Constante, e nao duas chamadas iguais a `$request->only(...)`: listagem e
     * CSV precisam filtrar identico -- exportacao que filtra diferente da tela
     * entrega um arquivo que nao corresponde ao que a pessoa estava vendo -- e
     * duas copias sempre acabam divergindo na primeira vez que um filtro novo
     * entra so em uma delas.
     *
     * @var array<int, string>
     */
    private const FILTROS = ['ativo', 'prestador_id', 'prestador_cnpj', 'search', 'vistoria'];

    public function __construct(
        private readonly FrotaService $service,
    ) {}

    /**
     * A frota: caminhao e situacao de vistoria na mesma tela.
     *
     * Eram duas listagens separadas, e o analista tinha de cruzar na cabeca
     * "este caminhao pode rodar?". Nenhuma das duas respondia: a de caminhoes
     * mostra `ativo`, que e flag de cadastro, e a de vistorias nao sabe quais
     * veiculos ficaram de fora.
     */
    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(self::FILTROS);

        $caminhoes = $this->service->listarFrota($perPage, $filtros);

        return Inertia::render('Tdap/Frota/Index', [
            'caminhoes'    => CaminhaoIndexResource::collection($caminhoes),
            'estatisticas' => fn () => $this->service->obterEstatisticasDaFrota(),
            'prestadores'  => fn () => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
            'filtros'      => $filtros,
            'canCreate'    => $request->user()?->can('tdap.caminhoes.create') ?? false,
            'canEdit'      => $request->user()?->can('tdap.caminhoes.edit') ?? false,
            'canDelete'    => $request->user()?->can('tdap.caminhoes.delete') ?? false,

            // Vistoria agora e coluna desta tela: o acesso a criar/ver vistoria
            // sai daqui, nao de um item de menu proprio.
            'canVerVistoria'   => $request->user()?->can('tdap.vistorias.view') ?? false,
            'canCriarVistoria' => $request->user()?->can('tdap.vistorias.create') ?? false,

            /*
            | A frota inteira para o seletor de "Nova Vistoria" do cabecalho.
            |
            | `lazy`: sao 132 registros que so interessam a quem abre o modal --
            | pendura-los em toda visita a listagem seria pagar o custo em cada
            | carga de pagina por causa de um botao. O Vue busca com
            | `router.reload({ only: ['frotaParaVistoria'] })` na abertura.
            |
            | Sob `tdap.vistorias.create`, e nao `tdap.caminhoes.view`: a
            | situacao de vistoria vai junto de cada placa, e quem nao pode
            | registrar vistoria nao tem o que fazer com esta lista. Mesma
            | separacao que tirou a serie historica do controller de caminhao.
            */
            'frotaParaVistoria' => Inertia::lazy(
                fn () => $request->user()?->can('tdap.vistorias.create')
                    ? CaminhaoIndexResource::collection($this->service->listarParaSelecaoDeVistoria())
                    : [],
            ),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return ExportadorCsv::baixar(
            $this->service->exportar($request->only(self::FILTROS)),
            'frota',
        );
    }

    public function create(Request $request): Response
    {
        $prestadores = Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']);

        // `?prestador_id=` vem do botao "Cadastrar caminhao" da ficha do
        // prestador: sem o pre-preenchimento o usuario tinha que reencontrar a
        // empresa numa lista de todos os prestadores ativos.
        $prestadorId = $request->integer('prestador_id') ?: null;

        return Inertia::render('Tdap/Frota/Create', [
            'prestadores'  => $prestadores,
            'prestadorId'  => $prestadores->contains('id', $prestadorId) ? $prestadorId : null,
        ]);
    }

    public function store(StoreCaminhaoRequest $request): RedirectResponse
    {
        $caminhao = $this->service->criar(
            CaminhaoDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('tdap.frota.show', $caminhao->id)
            ->with('success', "Caminhão {$caminhao->placa} cadastrado.");
    }

    public function show(Caminhao $caminhao, Request $request): Response
    {
        $caminhao = $this->service->obter($caminhao->id);

        return Inertia::render('Tdap/Frota/Show', [
            'caminhao'  => CaminhaoResource::make($caminhao),
            'canEdit'   => $request->user()?->can('tdap.caminhoes.edit') ?? false,
            'canDelete' => $request->user()?->can('tdap.caminhoes.delete') ?? false,
        ]);
    }

    public function edit(Caminhao $caminhao): Response
    {
        return Inertia::render('Tdap/Frota/Edit', [
            'caminhao'    => CaminhaoResource::make($caminhao->load('prestador')),
            'prestadores' => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
        ]);
    }

    public function update(UpdateCaminhaoRequest $request, Caminhao $caminhao): RedirectResponse
    {
        $atualizado = $this->service->atualizar(
            $caminhao->id,
            CaminhaoDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('tdap.frota.show', $atualizado->id)
            ->with('success', "Caminhão {$atualizado->placa} atualizado.");
    }

    public function destroy(Caminhao $caminhao, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.caminhoes.delete')) {
            abort(403);
        }

        $placa = $caminhao->placa;

        try {
            $this->service->deletar($caminhao->id);
        } catch (\DomainException $e) {
            // Alocado em cronograma vivo: mensagem de negocio, nao 500.
            return redirect()
                ->route('tdap.frota.show', $caminhao->id)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tdap.frota.index')
            ->with('success', "Caminhão {$placa} excluído.");
    }
}
