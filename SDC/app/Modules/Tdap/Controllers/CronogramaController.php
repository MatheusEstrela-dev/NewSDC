<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Tdap\DTOs\CronogramaDTO;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Pmda\Models\Comunidade;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Requests\StoreCronogramaRequest;
use App\Modules\Tdap\Requests\UpdateCronogramaRequest;
use App\Modules\Tdap\Resources\CronogramaIndexResource;
use App\Modules\Tdap\Resources\CronogramaResource;
use App\Modules\Tdap\Services\CronogramaService;
use App\Modules\Tdap\Support\PoliticaPontoCaptacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CronogramaController extends Controller
{
    public function __construct(
        private readonly CronogramaService $service,
        private readonly PoliticaPontoCaptacao $politicaPontos,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $filtros = $request->only(['estado', 'ata_id', 'prestador_id', 'municipio_id', 'search']);

        $cronogramas = $this->service->listar($perPage, $filtros);

        return Inertia::render('Tdap/Cronogramas/Index', [
            'cronogramas'  => CronogramaIndexResource::collection($cronogramas),
            'estatisticas' => fn () => $this->service->obterEstatisticas(),
            'atas'         => fn () => Ata::ativo()->orderByDesc('dt_inicio')->get(['id', 'numero']),
            'prestadores'  => fn () => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
            'municipios'   => fn () => $this->service->municipiosDosLotes(),
            'filtros'      => $filtros,
            'canCreate'    => $request->user()?->can('tdap.cronogramas.create') ?? false,
            'canEdit'      => $request->user()?->can('tdap.cronogramas.edit') ?? false,
            'canAtivar'    => $request->user()?->can('tdap.cronogramas.ativar') ?? false,
            'canDelete'    => $request->user()?->can('tdap.cronogramas.delete') ?? false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(['estado', 'ata_id', 'prestador_id', 'municipio_id', 'search', 'data_inicio', 'data_fim']);
        $data = $this->service->exportar($filtros);

        $filename = 'cronogramas_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para Excel reconhecer acentuacao.
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if (! empty($data)) {
                fputcsv($handle, array_keys($data[0]), ';');
            }

            foreach ($data as $row) {
                fputcsv($handle, array_values($row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tdap/Cronogramas/Create', $this->propsDoFormulario());
    }

    /**
     * Opcoes do formulario de cadastro/edicao.
     *
     * `pontosCaptacao` vem agrupado por municipio e ja filtrado pela
     * PoliticaPontoCaptacao (PMDA aprovado), so para os municipios dos lotes
     * ativos -- antes ia o estado inteiro e o filtro era so no navegador.
     *
     * @return array<string, mixed>
     */
    private function propsDoFormulario(?Cronograma $cronograma = null): array
    {
        $lotes = Lote::ativo()->with(['ata:id,numero', 'municipios:id,nome,uf', 'prestador:id,nome,cnpj'])->get();

        $municipioIds = $lotes->flatMap(fn (Lote $l) => $l->municipios->pluck('id'))
            ->push($cronograma?->municipio_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $vinculados = $cronograma?->pontosCaptacao()->allRelatedIds()->map(fn ($id) => (int) $id)->all() ?? [];

        return [
            'atas'           => Ata::ativo()->orderByDesc('dt_inicio')->get(['id', 'numero', 'dt_inicio', 'dt_final']),
            'lotes'          => $lotes,
            'municipios'     => Municipio::catalogo(),
            'prestadores'    => Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']),
            'pontosCaptacao' => $this->politicaPontos->opcoesPorMunicipio($municipioIds, $vinculados),
            'exigePmda'      => $this->politicaPontos->exigePmda(),
            'podeVerPmda'    => request()->user()?->can('pmda.planos.view') ?? false,
        ];
    }

    public function store(StoreCronogramaRequest $request): RedirectResponse
    {
        $cronograma = $this->service->criar(CronogramaDTO::fromRequest($request->validated()));

        return redirect()
            ->route('tdap.cronogramas.show', $cronograma->id)
            ->with('success', "Cronograma {$cronograma->numero} criado em rascunho. Aloque caminhoes para ativar.");
    }

    public function show(Cronograma $cronograma, Request $request): Response
    {
        $cronograma = $this->service->obter($cronograma->id);
        [$podeAtivar, $motivoBloqueio] = $this->service->podeAtivar($cronograma);

        return Inertia::render('Tdap/Cronogramas/Show', [
            'cronograma'       => CronogramaResource::make($cronograma),
            'pontosCaptacao'   => $this->pontosParaExibir($cronograma),
            // As comunidades do municipio atendido, com a populacao que a
            // alocacao usa para calcular agua prevista e viagens. Sem elas o
            // modal so aceitaria os dois numeros digitados a mao.
            'comunidades'      => Comunidade::query()
                ->where('municipio_id', $cronograma->municipio_id)
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->orderBy('nome')
                ->get(['id', 'nome', 'pop_atendida']),
            'podeAtivar'       => $podeAtivar,
            'motivoBloqueio'   => $motivoBloqueio,
            'canEdit'          => $request->user()?->can('tdap.cronogramas.edit') ?? false,
            'canDelete'        => $request->user()?->can('tdap.cronogramas.delete') ?? false,
            'canAtivar'        => $request->user()?->can('tdap.cronogramas.ativar') ?? false,
            'canProrrogar'     => $request->user()?->can('tdap.cronogramas.prorrogar') ?? false,
            'canAlocarCaminhao' => $request->user()?->can('tdap.cronogramas.edit') ?? false,
            'canValidarViagem' => $request->user()?->can('tdap.viagens.validar') ?? false,
        ]);
    }

    /**
     * Dados do documento impresso do cronograma (BasePrintModal no front).
     *
     * JSON sob demanda, no mesmo molde da ficha do PMDA (pmda.planos.ficha): a
     * listagem nao carrega caminhoes nem pontos, e buscar so ao clicar evita
     * pesar a paginacao inteira por causa de um botao.
     */
    public function impressao(Cronograma $cronograma): JsonResponse
    {
        $cronograma = $this->service->obter($cronograma->id);

        return response()->json([
            'cronograma'      => CronogramaResource::make($cronograma)->resolve(),
            'pontos_captacao' => $this->pontosParaExibir($cronograma),
        ]);
    }

    /**
     * Ativado (ativo ou ja encerrado): o retrato gravado na ativacao.
     * Rascunho, ou ativado antes do retrato existir: os vinculos de agora,
     * cada um com o PMDA que o autorizou (nulo = legado).
     *
     * @return list<array<string, mixed>>
     */
    private function pontosParaExibir(Cronograma $cronograma): array
    {
        return $cronograma->stored_pmda_ponto !== null
            ? $cronograma->stored_pmda_ponto
            : $this->politicaPontos->apresentarVinculados($cronograma->pontosCaptacao);
    }

    public function edit(Cronograma $cronograma): Response
    {
        return Inertia::render('Tdap/Cronogramas/Edit', [
            'cronograma' => CronogramaResource::make($cronograma->load(['ata', 'lote', 'municipio', 'prestador', 'pontosCaptacao'])),
            ...$this->propsDoFormulario($cronograma),
        ]);
    }

    public function update(UpdateCronogramaRequest $request, Cronograma $cronograma): RedirectResponse
    {
        try {
            $atualizado = $this->service->atualizar($cronograma->id, CronogramaDTO::fromRequest($request->validated()));

            return redirect()
                ->route('tdap.cronogramas.show', $atualizado->id)
                ->with('success', "Cronograma {$atualizado->numero} atualizado.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ativar(Cronograma $cronograma): RedirectResponse
    {
        try {
            $this->service->ativar($cronograma->id);

            return redirect()
                ->route('tdap.cronogramas.show', $cronograma->id)
                ->with('success', "Cronograma {$cronograma->numero} ativado.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function encerrar(Request $request, Cronograma $cronograma): RedirectResponse
    {
        try {
            $obs = (string) $request->input('observacao', '');
            $this->service->encerrar($cronograma->id, $obs !== '' ? $obs : null);

            return redirect()
                ->route('tdap.cronogramas.show', $cronograma->id)
                ->with('success', "Cronograma {$cronograma->numero} encerrado.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function prorrogar(Request $request, Cronograma $cronograma): RedirectResponse
    {
        $request->validate([
            'dt_inicio_prorrogacao' => ['required', 'date'],
            'dt_final_prorrogacao'  => ['required', 'date', 'after_or_equal:dt_inicio_prorrogacao'],
            'justificativa'         => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->prorrogar(
                $cronograma->id,
                (string) $request->input('dt_inicio_prorrogacao'),
                (string) $request->input('dt_final_prorrogacao'),
                $request->input('justificativa'),
            );

            return redirect()
                ->route('tdap.cronogramas.show', $cronograma->id)
                ->with('success', "Cronograma {$cronograma->numero} prorrogado.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function arquivar(Cronograma $cronograma): RedirectResponse
    {
        $this->service->arquivar($cronograma->id);

        return back()->with('success', "Cronograma {$cronograma->numero} arquivado.");
    }

    public function desarquivar(Cronograma $cronograma): RedirectResponse
    {
        $this->service->desarquivar($cronograma->id);

        return back()->with('success', "Cronograma {$cronograma->numero} desarquivado.");
    }

    public function destroy(Cronograma $cronograma, Request $request): RedirectResponse
    {
        if (! $request->user()?->can('tdap.cronogramas.delete')) {
            abort(403);
        }

        try {
            $numero = $cronograma->numero;
            $this->service->deletar($cronograma->id);

            return redirect()
                ->route('tdap.cronogramas.index')
                ->with('success', "Cronograma {$numero} excluido.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
