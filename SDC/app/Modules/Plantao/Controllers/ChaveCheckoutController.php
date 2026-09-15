<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Exceptions\ReservaInvalidaException;
use App\Modules\Plantao\Models\ViaturaReserva;
use App\Modules\Plantao\Requests\ChaveCheckoutRequest;
use App\Modules\Plantao\Services\ReservaViaturaService;
use Illuminate\Http\RedirectResponse;

class ChaveCheckoutController extends Controller
{
    public function __construct(
        private readonly ReservaViaturaService $reservaService
    ) {
    }

    public function __invoke(ChaveCheckoutRequest $request, ViaturaReserva $reserva): RedirectResponse
    {
        try {
            $this->reservaService->checkout($reserva->id, $request->validated(), $request->user());
        } catch (ReservaInvalidaException|MovimentacaoInvalidaException $e) {
            return back()->withErrors(['chave' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Chave devolvida. Viatura disponivel.');
    }
}
