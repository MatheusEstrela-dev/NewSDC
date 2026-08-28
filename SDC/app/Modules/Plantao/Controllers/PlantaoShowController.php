<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\DTOs\PlantaoDetailDTO;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Services\PlantaoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlantaoShowController extends Controller
{
    public function __construct(
        private readonly PlantaoService $plantaoService
    ) {
    }

    public function __invoke(Request $request, Plantao $plantao): Response
    {
        // Armadilha #6 do plano: sem eager load aqui, a tela do turno vira
        // N+1 (um SELECT por snapshot/movimentacao/usuario referenciado).
        $plantao->load([
            'snapshots.viatura',
            'movimentacoes.viatura',
            'movimentacoes.condutor',
            'plantonista',
            'plantonistaSaida',
            'encerradoPor',
            'aceitoPor',
        ]);

        $user = $request->user();
        $podeEditar = $user !== null && $this->plantaoService->podeEditar($plantao, $user);

        return Inertia::render('Plantao/Show', [
            'plantao' => PlantaoDetailDTO::fromModel($plantao, $podeEditar),
            'canRelatorio' => (bool) $user?->can('plantao.passagem.relatorio'),
        ]);
    }
}
