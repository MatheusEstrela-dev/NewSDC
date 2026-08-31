<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\EscalaInvalidaException;
use App\Modules\Plantao\Models\Escala;
use App\Modules\Plantao\Requests\StoreEscalaItemRequest;
use App\Modules\Plantao\Services\EscalaService;
use Illuminate\Http\RedirectResponse;

class EscalaItemStoreController extends Controller
{
    public function __construct(
        private readonly EscalaService $escalaService
    ) {
    }

    public function __invoke(StoreEscalaItemRequest $request, Escala $escala): RedirectResponse
    {
        try {
            $item = $this->escalaService->adicionarItem($escala, $request->validated());
        } catch (EscalaInvalidaException $e) {
            return back()->withErrors(['vaga' => $e->getMessage()])->withInput();
        }

        // Descanso curto nao impede a escala: informa e segue. Vai como
        // 'warning' e nao como erro para nao parecer que a vaga foi recusada.
        $alertas = $this->escalaService->alertasDeDescanso($item);

        $resposta = back()->with('success', 'Vaga preenchida.');

        return $alertas === []
            ? $resposta
            : $resposta->with('warning', implode(' ', $alertas));
    }
}
