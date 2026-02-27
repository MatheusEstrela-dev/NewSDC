<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\TreinamentoService;
use Illuminate\Http\Request;

class TreinamentoStoreController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string',
            'tipo' => 'required|string',
            'carga_horaria' => 'required|integer|min:1',
        ]);

        $treinamento = $this->treinamentoService->create($validated);

        return redirect()->route('treinamentos.show', $treinamento->id)
            ->with('success', 'Treinamento cadastrado com sucesso!');
    }
}
