<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\TreinamentoService;
use Illuminate\Http\Request;

class TreinamentoLiberarPresencaController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(Request $request, Treinamento $treinamento)
    {
        try {
            $this->treinamentoService->liberarPresenca($treinamento, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['presenca' => $e->getMessage()]);
        }

        return back()->with('success', 'Presenca liberada. Ja e possivel fazer o check-in dos inscritos.');
    }
}
