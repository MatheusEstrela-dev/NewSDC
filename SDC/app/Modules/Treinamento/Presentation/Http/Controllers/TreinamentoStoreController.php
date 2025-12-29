<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Application\UseCases\Treinamento\CreateTreinamentoUseCase;
use App\Modules\Treinamento\Presentation\Http\Requests\CreateTreinamentoRequest;
use Illuminate\Http\RedirectResponse;

class TreinamentoStoreController extends Controller
{
    public function __construct(
        private readonly CreateTreinamentoUseCase $createUseCase
    ) {
    }

    public function __invoke(CreateTreinamentoRequest $request): RedirectResponse
    {
        $treinamento = $this->createUseCase->execute(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('treinamentos.show', $treinamento->id)
            ->with('success', 'Treinamento criado com sucesso!');
    }
}
