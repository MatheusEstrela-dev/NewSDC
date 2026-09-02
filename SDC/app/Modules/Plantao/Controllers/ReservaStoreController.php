<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\ReservaInvalidaException;
use App\Modules\Plantao\Requests\StoreReservaRequest;
use App\Modules\Plantao\Services\ReservaViaturaService;
use Illuminate\Http\RedirectResponse;

class ReservaStoreController extends Controller
{
    public function __construct(
        private readonly ReservaViaturaService $reservaService
    ) {
    }

    public function __invoke(StoreReservaRequest $request): RedirectResponse
    {
        try {
            // O agente e sempre quem esta autenticado. Reservar para terceiros
            // nao existe de proposito: a chave e nominal, e o check-in confere
            // reserva contra o usuario da sessao.
            $this->reservaService->agendar($request->validated(), $request->user());
        } catch (ReservaInvalidaException $e) {
            return back()->withErrors(['reserva' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Reserva registrada.');
    }
}
