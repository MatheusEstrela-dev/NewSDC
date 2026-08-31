<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Escala;
use App\Modules\Plantao\Services\EscalaService;
use Illuminate\Http\RedirectResponse;

class EscalaDestroyController extends Controller
{
    public function __construct(
        private readonly EscalaService $escalaService
    ) {
    }

    public function __invoke(Escala $escala): RedirectResponse
    {
        $rotulo = $escala->rotulo();

        $this->escalaService->excluir($escala);

        return redirect()
            ->route('plantao.escala.index')
            ->with('success', 'Escala de '.$rotulo.' excluida.');
    }
}
