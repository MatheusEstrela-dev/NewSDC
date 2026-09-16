<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\DTOs\CaminhaoDTO;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Requests\StoreCaminhaoRequest;
use App\Modules\Tdap\Requests\UpdateCaminhaoRequest;
use App\Modules\Tdap\Resources\CaminhaoIndexResource;
use App\Modules\Tdap\Resources\CaminhaoResource;
use App\Modules\Tdap\Services\CaminhaoService;
use App\Modules\Tdap\Support\VigenciaAta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaminhaoController extends Controller
{
    public function __construct(
        private readonly CaminhaoService $service,
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
        $filtros = $request->only(['ativo', 'prestador_id', 'search', 'vistoria']);

        $caminhoes = $this->service->listarFrota($perPage, $filtros);

        return Inertia::render('Tdap/Caminhoes/Index', [
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
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        // Mesma allowlist da listagem: o CSV precisa corresponder ao que a tela
        // estava mostrando, filtro de vistoria incluido.
        $filtros = $request->only(['ativo', 'prestador_id', 'search', 'vistoria']);
        $data = $this->service->exportar($filtros);

        $filename = 'caminhoes_'.now()->format('Y-m-d_H-i-s').'.csv';

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

    /**
     * Serie historica de vistorias de um caminhao, em JSON.
     *
     * O modal da listagem consome isto. A vistoria vigente responde "pode
     * rodar HOJE"; a serie responde "este veiculo e confiavel?" -- reprovado
     * tres vezes seguidas e um caminhao com problema, e isso so aparece quando
     * as inspecoes ficam lado a lado.
     */
    public function vistorias(Caminhao $caminhao): JsonResponse
    {
        $vistorias = $caminhao->vistorias()
            ->get(['id', 'placa_id', 'data', 'parecer', 'nome', 'ficha', 'lacre', 'edital', 'observacoes'])
            ->map(fn (Vistoria $v) => [
                'id'             => $v->id,
                'data'           => $v->data?->toDateString(),
                'parecer'        => $v->parecer?->value,
                'parecer_label'  => $v->parecer?->label(),
                'vistoriador'    => $v->nome,
                'ficha'          => $v->ficha,
                'lacre'          => $v->lacre,
                'edital'         => $v->edital,
                'observacoes'    => $v->observacoes,
                'vigente'        => (bool) $v->esta_vigente,
                // Mesma vigencia assinada do resto do modulo: negativo = venceu.
                'dias_restantes' => $v->data === null ? null : VigenciaAta::diasRestantes(
                    $v->data->copy()->addMonths(Vistoria::VIGENCIA_MESES),
                ),
            ])
            ->values();

        return response()->json([
            'caminhao' => [
                'id'    => $caminhao->id,
                'placa' => $caminhao->placa,
                'marca' => $caminhao->marca,
                'modelo' => $caminhao->modelo,
            ],
            'vistorias' => $vistorias,
        ]);
    }

    public function create(Request $request): Response
    {
        $prestadores = Prestador::ativo()->orderBy('nome')->get(['id', 'nome', 'cnpj']);

        // `?prestador_id=` vem do botao "Cadastrar caminhao" da ficha do
        // prestador: sem o pre-preenchimento o usuario tinha que reencontrar a
        // empresa numa lista de todos os prestadores ativos.
        $prestadorId = $request->integer('prestador_id') ?: null;

        return Inertia::render('Tdap/Caminhoes/Create', [
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
            ->route('tdap.caminhoes.show', $caminhao->id)
            ->with('success', "Caminhão {$caminhao->placa} cadastrado.");
    }

    public function show(Caminhao $caminhao, Request $request): Response
    {
        $caminhao = $this->service->obter($caminhao->id);

        return Inertia::render('Tdap/Caminhoes/Show', [
            'caminhao'  => CaminhaoResource::make($caminhao),
            'canEdit'   => $request->user()?->can('tdap.caminhoes.edit') ?? false,
            'canDelete' => $request->user()?->can('tdap.caminhoes.delete') ?? false,
        ]);
    }

    public function edit(Caminhao $caminhao): Response
    {
        return Inertia::render('Tdap/Caminhoes/Edit', [
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
            ->route('tdap.caminhoes.show', $atualizado->id)
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
                ->route('tdap.caminhoes.show', $caminhao->id)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tdap.caminhoes.index')
            ->with('success', "Caminhão {$placa} excluído.");
    }
}
