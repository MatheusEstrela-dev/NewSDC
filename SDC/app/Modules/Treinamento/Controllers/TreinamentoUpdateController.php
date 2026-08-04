<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\UpdateTreinamentoRequest;
use App\Modules\Treinamento\Services\TreinamentoService;

class TreinamentoUpdateController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(UpdateTreinamentoRequest $request, int $id)
    {
        $this->treinamentoService->update($id, $request->validated());

        return redirect()->route('treinamentos.show', $id)
            ->with('success', 'Treinamento atualizado com sucesso!');
    }
}
