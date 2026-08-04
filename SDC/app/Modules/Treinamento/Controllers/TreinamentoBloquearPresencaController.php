<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\TreinamentoService;

class TreinamentoBloquearPresencaController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(Treinamento $treinamento)
    {
        $this->treinamentoService->bloquearPresenca($treinamento);

        return back()->with('success', 'Presenca bloqueada.');
    }
}
