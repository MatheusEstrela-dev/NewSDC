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
        try {
            $treinamento = $this->treinamentoService->create($request->validated());
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('treinamentos.show', $treinamento->id)
            ->with('success', 'Treinamento cadastrado com sucesso!');
    }
}
