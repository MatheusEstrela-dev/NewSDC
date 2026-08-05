<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\StoreTreinamentoRequest;
use App\Modules\Treinamento\Services\TreinamentoService;

class TreinamentoStoreController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(StoreTreinamentoRequest $request)
    {
        $treinamento = $this->treinamentoService->create($request->validated());

        return redirect()->route('treinamentos.show', $treinamento->id)
            ->with('success', 'Treinamento cadastrado com sucesso!');
    }
}
