<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\DTOs\ReservaListDTO;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Enums\StatusReserva;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\ViaturaReserva;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela do scanner. Entrega tambem a agenda do proprio agente para hoje: quem
 * abre a camera quase sempre tem uma reserva a caminho, e ver "sua reserva da
 * SW4 comeca as 14h" responde de antemao a recusa mais comum do scan.
 */
class ChaveScanPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $minhas = ViaturaReserva::query()
            ->with(['viatura:id,prefixo,placa,modelo'])
            ->where('agente_id', $user->id)
            ->whereIn('status', [
                StatusReserva::AGENDADA->value,
                StatusReserva::EM_USO->value,
            ])
            ->where('fim_previsto', '>=', now()->startOfDay())
            ->orderBy('inicio_previsto')
            ->limit(10)
            ->get();

        return Inertia::render('Plantao/ChaveScan', [
            'minhasReservas' => ReservaListDTO::collection($minhas),
            'toleranciaMinutos' => (int) config('plantao.reservas.tolerancia_checkin_minutos', 30),
            // Ja em {value, label} pelo toSelectArray(): o FormSelect nao
            // entende enum cru, e remapear na tela duplicaria os rotulos.
            'niveis' => NivelCombustivel::toSelectArray(),
            // Mesma amarracao da tela de viaturas: sem isto a movimentacao
            // aberta pelo scan nasceria sem plantao_id e se perderia a resposta
            // a "quem estava de servico quando a viatura saiu".
            'plantaoAtivoId' => Plantao::query()
                ->where('status', StatusPlantao::ATIVO->value)
                ->orderByDesc('data')
                ->orderByDesc('id')
                ->value('id'),
        ]);
    }
}
