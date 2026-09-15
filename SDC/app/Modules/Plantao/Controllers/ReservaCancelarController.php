<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\ReservaInvalidaException;
use App\Modules\Plantao\Models\ViaturaReserva;
use App\Modules\Plantao\Requests\CancelarReservaRequest;
use App\Modules\Plantao\Services\ReservaViaturaService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ReservaCancelarController extends Controller
{
    public function __construct(
        private readonly ReservaViaturaService $reservaService
    ) {
    }

    public function __invoke(CancelarReservaRequest $request, ViaturaReserva $reserva): RedirectResponse
    {
        $user = $request->user();

        // A rota exige apenas `plantao.reservas.create`, que todo agente tem.
        // A checagem fina mora aqui: cancelar a PROPRIA reserva e ato de
        // operacao diaria; derrubar a de outra pessoa exige supervisao. Mesmo
        // desenho de PlantaoDestroyController -- 403, nao 404.
        if ($reserva->agente_id !== $user->id && !$user->can('plantao.reservas.manage')) {
            throw new AccessDeniedHttpException(
                "Esta reserva e de {$reserva->agente_nome}. Voce nao pode cancela-la."
            );
        }

        try {
            $this->reservaService->cancelar($reserva->id, $user, $request->validated('motivo'));
        } catch (ReservaInvalidaException $e) {
            return back()->withErrors(['reserva' => $e->getMessage()]);
        }

        return back()->with('success', 'Reserva cancelada.');
    }
}
