<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\EscalaInvalidaException;
use App\Modules\Plantao\Requests\StoreEscalaRequest;
use App\Modules\Plantao\Services\EscalaService;
use Illuminate\Http\RedirectResponse;

/**
 * Abre a escala de um mes em rascunho. Idempotente: o mes que ja existe e
 * apenas devolvido, porque o indice unico parcial (ano, mes) rejeitaria um
 * segundo registro e o duplo-clique no botao nao pode virar erro de banco.
 */
class EscalaStoreController extends Controller
{
    public function __construct(
        private readonly EscalaService $escalaService
    ) {
    }

    public function __invoke(StoreEscalaRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        try {
            $escala = $this->escalaService->obterOuCriar(
                (int) $dados['ano'],
                (int) $dados['mes'],
                $request->user(),
            );
        } catch (EscalaInvalidaException $e) {
            return back()->withErrors(['escala' => $e->getMessage()]);
        }

        return redirect()
            ->route('plantao.escala.index', ['ano' => $escala->ano, 'mes' => $escala->mes])
            ->with('success', 'Escala de '.$escala->rotulo().' aberta em rascunho.');
    }
}
