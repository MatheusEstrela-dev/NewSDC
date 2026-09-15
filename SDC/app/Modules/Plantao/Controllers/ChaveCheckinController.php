<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Exceptions\ReservaInvalidaException;
use App\Modules\Plantao\Models\ViaturaReserva;
use App\Modules\Plantao\Requests\ChaveCheckinRequest;
use App\Modules\Plantao\Services\ReservaViaturaService;
use Illuminate\Http\RedirectResponse;

/**
 * Retirada da chave: abre a movimentacao e poe a reserva EM_USO.
 *
 * As duas excecoes tratadas vem de camadas diferentes e as duas sao esperadas:
 * ReservaInvalidaException e a agenda recusando (fora da janela, reserva de
 * outra pessoa), MovimentacaoInvalidaException e a guarda de frota recusando
 * (hodometro regressivo, viatura em manutencao). Nenhuma das duas foi
 * reimplementada aqui -- ambas continuam nos services donos da regra.
 */
class ChaveCheckinController extends Controller
{
    public function __construct(
        private readonly ReservaViaturaService $reservaService
    ) {
    }

    public function __invoke(ChaveCheckinRequest $request, ViaturaReserva $reserva): RedirectResponse
    {
        try {
            $this->reservaService->checkin($reserva->id, $request->validated(), $request->user());
        } catch (ReservaInvalidaException|MovimentacaoInvalidaException $e) {
            return back()->withErrors(['chave' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Chave retirada. Boa viagem.');
    }
}
