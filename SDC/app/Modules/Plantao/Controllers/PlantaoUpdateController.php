<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Requests\UpdatePlantaoRequest;
use App\Modules\Plantao\Services\PlantaoService;
use Illuminate\Http\RedirectResponse;

class PlantaoUpdateController extends Controller
{
    public function __construct(
        private readonly PlantaoService $plantaoService
    ) {
    }

    public function __invoke(UpdatePlantaoRequest $request, Plantao $plantao): RedirectResponse
    {
        $usuario = $request->user();

        // Decisao 2 do plano: so o dono edita, e so enquanto o turno esta
        // ATIVO. PENDENTE_ACEITE/FINALIZADO* ja tem snapshot declarado (e
        // possivelmente aceito) - editar reescreveria historico. A excecao de
        // supervisao (`encerrar_alheio`) esta dentro de podeEditar(). Falta
        // de autorizacao, nao erro de formulario -> 403.
        abort_unless(
            $usuario !== null && $this->plantaoService->podeEditar($plantao, $usuario),
            403,
            'Voce nao pode editar este turno.'
        );

        $this->plantaoService->update($plantao->id, $request->validated());

        return redirect()->route('plantao.show', $plantao->id)
            ->with('success', 'Turno atualizado.');
    }
}
