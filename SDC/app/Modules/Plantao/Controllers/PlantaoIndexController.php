<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\DTOs\PlantaoListDTO;
use App\Modules\Plantao\DTOs\SnapshotDTO;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Services\PassagemServicoService;
use App\Modules\Plantao\Services\PlantaoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlantaoIndexController extends Controller
{
    public function __construct(
        private readonly PlantaoService $plantaoService,
        private readonly PassagemServicoService $passagemService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['status', 'periodo', 'search']);

        $plantoes = $this->plantaoService->list($filters, 15);
        $statistics = $this->plantaoService->getStatistics($filters);

        $plantoesData = [
            'data' => PlantaoListDTO::collection($plantoes->items()),
            'pagination' => [
                'current_page' => $plantoes->currentPage(),
                'per_page' => $plantoes->perPage(),
                'total' => $plantoes->total(),
                'last_page' => $plantoes->lastPage(),
                'from' => $plantoes->firstItem(),
                'to' => $plantoes->lastItem(),
            ],
        ];

        return Inertia::render('Plantao/PlantaoIndex', [
            'plantoes' => $plantoesData,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusPlantao::toSelectArray(),
                'periodos' => PeriodoPlantao::toSelectArray(),
                // Consumido pelo EncerrarTurnoModal (Task 10): select de nivel
                // de combustivel por viatura na conferencia de encerramento.
                'niveis' => NivelCombustivel::toSelectArray(),
            ],
            // Listas, nao "o mais recente": a abertura de turno e
            // deliberadamente nao-bloqueante (spec 4.2), entao pode existir mais
            // de um ATIVO (periodos diferentes) e mais de um PENDENTE_ACEITE.
            // Com ->first(), o turno mais antigo ficava sem botao de encerrar ou
            // aceitar em lugar nenhum - o travamento que a secao 4.3 quis evitar.
            'turnosAtivos' => $this->turnosAtivos(),
            'turnosPendentes' => $this->turnosPendentes(),
            'canEncerrar' => (bool) $request->user()?->can('plantao.passagem.encerrar'),
            'canAceitar' => (bool) $request->user()?->can('plantao.passagem.aceitar'),
            'canRelatorio' => (bool) $request->user()?->can('plantao.passagem.relatorio'),
        ]);
    }

    /**
     * Todos os turnos ATIVO, do mais recente para o mais antigo. A tela
     * renderiza um botao de encerrar por turno: nenhum fica sem saida.
     *
     * @return list<array<string,mixed>>
     */
    private function turnosAtivos(): array
    {
        return Plantao::query()
            ->where('status', StatusPlantao::ATIVO->value)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Plantao $turno) => [
                'id' => $turno->id,
                'data' => $turno->data?->format('d/m/Y'),
                'periodo' => $turno->periodo?->labelCurto(),
                'plantonista_nome' => $turno->plantonista_nome,
                'plantonista_saida_nome' => $turno->plantonista_saida_nome,
                'snapshot_sugerido' => $this->passagemService->montarSnapshotSugerido($turno),
            ])
            ->all();
    }

    /**
     * Todos os turnos PENDENTE_ACEITE. A tela renderiza um banner por
     * pendencia: nenhuma fica sem caminho de aceite.
     *
     * @return list<array<string,mixed>>
     */
    private function turnosPendentes(): array
    {
        return Plantao::query()
            ->where('status', StatusPlantao::PENDENTE_ACEITE->value)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->with(['snapshots.viatura:id,exclusiva_sobreaviso', 'encerradoPor:id,name'])
            ->get()
            ->map(fn (Plantao $turno) => [
                'id' => $turno->id,
                'data' => $turno->data?->format('d/m/Y'),
                'periodo' => $turno->periodo?->labelCurto(),
                'plantonista_nome' => $turno->plantonista_nome,
                'encerrado_em' => $turno->encerrado_em?->format('d/m/Y H:i'),
                // Quando difere do dono do turno, o encerramento foi por terceiro
                // e a interface precisa deixar isso visivel (spec 4.3).
                'encerrado_por_terceiro' => $turno->encerrado_por_id !== null
                    && (int) $turno->encerrado_por_id !== (int) $turno->plantonista_id,
                'encerrado_por_nome' => $turno->encerradoPor?->name,
                'snapshots' => SnapshotDTO::collection($turno->snapshots),
            ])
            ->all();
    }
}
