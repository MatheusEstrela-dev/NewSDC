<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\EscalaInvalidaException;
use App\Modules\Plantao\Models\Escala;
use App\Modules\Plantao\Services\EscalaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Publica a escala: a unica transicao que avisa os plantonistas. Enquanto o mes
 * esta em rascunho o montador mexe a vontade sem gerar ruido no sino de
 * ninguem.
 */
class EscalaPublicarController extends Controller
{
    public function __construct(
        private readonly EscalaService $escalaService
    ) {
    }

    public function __invoke(Request $request, Escala $escala): RedirectResponse
    {
        try {
            $this->escalaService->publicar($escala, $request->user());
        } catch (EscalaInvalidaException $e) {
            return back()->withErrors(['escala' => $e->getMessage()]);
        }

        return back()->with('success', 'Escala publicada. Os plantonistas foram notificados.');
    }
}
