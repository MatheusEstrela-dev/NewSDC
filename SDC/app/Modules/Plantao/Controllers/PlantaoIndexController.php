<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Plantao\DTOs\PlantaoListDTO;
use App\Modules\Plantao\DTOs\SnapshotDTO;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Models\TipoTurno;
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
        $user = $request->user();

        $plantoesData = [
            'data' => PlantaoListDTO::collection(
                $plantoes->items(),
                fn (Plantao $turno) => $user !== null && $this->plantaoService->podeEditar($turno, $user)
            ),
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
                'periodos' => $this->opcoesDePeriodo(),
                // Consumido pelo EncerrarTurnoModal (Task 10): select de nivel
                // de combustivel por viatura na conferencia de encerramento.
                'niveis' => NivelCombustivel::toSelectArray(),
            ],
            // Listas, nao "o mais recente": a abertura de turno e
            // deliberadamente nao-bloqueante (spec 4.2), entao pode existir mais
            // de um ATIVO (periodos diferentes) e mais de um PENDENTE_ACEITE.
            // Com ->first(), o turno mais antigo ficava sem botao de encerrar ou
            // aceitar em lugar nenhum - o travamento que a secao 4.3 quis evitar.
            'turnosAtivos' => $this->turnosAtivos($user),
            'turnosPendentes' => $this->turnosPendentes(),
            'canEncerrar' => (bool) $user?->can('plantao.passagem.encerrar'),
            'canAceitar' => (bool) $user?->can('plantao.passagem.aceitar'),
            'canRelatorio' => (bool) $user?->can('plantao.passagem.relatorio'),
            // Sem isto a escala existe mas nao ha como chegar nela pela
            // interface -- o mesmo furo (F-6) da release anterior, em que a
            // maquina de estados nao tinha porta de entrada.
            'canEscala' => (bool) $user?->can('plantao.escala.view'),
        ]);
    }

    /**
     * Opcoes do filtro de periodo, vindas da tabela de tipos de turno.
     *
     * Aqui entram TODOS os tipos ativos, inclusive os nao escalaveis: o filtro
     * olha turnos ja gravados, e um turno EXTRAORDINARIO precisa ser
     * encontravel mesmo nao podendo ser escalado.
     *
     * @return list<array{value:string,label:string}>
     */
    private function opcoesDePeriodo(): array
    {
        return TipoTurno::ativos()
            ->get()
            ->map(fn (TipoTurno $tipo) => [
                'value' => $tipo->codigo,
                'label' => $tipo->label(),
            ])
            ->all();
    }

    /**
     * Todos os turnos ATIVO, do mais recente para o mais antigo. A tela
     * renderiza um botao de encerrar por turno: nenhum fica sem saida.
     *
     * @return list<array<string,mixed>>
     */
    private function turnosAtivos(?User $user): array
    {
        // Decidido aqui, nao no frontend: usuario so encerra o proprio turno,
        // a menos que tenha a permissao de excecao (spec 4.3 ajustada -
        // handshake que travaria se so o dono pudesse destravar).
        $podeEncerrarAlheio = (bool) $user?->can('plantao.passagem.encerrar_alheio');

        return Plantao::query()
            ->where('status', StatusPlantao::ATIVO->value)
            ->with('tipoTurno')
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Plantao $turno) => [
                'id' => $turno->id,
                'data' => $turno->data?->format('d/m/Y'),
                'periodo' => $turno->tipoTurno?->labelCurto(),
                'plantonista_nome' => $turno->plantonista_nome,
                'plantonista_saida_nome' => $turno->plantonista_saida_nome,
                'snapshot_sugerido' => $this->passagemService->montarSnapshotSugerido($turno),
                'pode_encerrar' => $podeEncerrarAlheio
                    || ($user !== null && (int) $turno->plantonista_id === (int) $user->id),
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
            ->with(['tipoTurno', 'snapshots.viatura:id,exclusiva_sobreaviso', 'encerradoPor:id,name'])
            ->get()
            ->map(fn (Plantao $turno) => [
                'id' => $turno->id,
                'data' => $turno->data?->format('d/m/Y'),
                'periodo' => $turno->tipoTurno?->labelCurto(),
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
