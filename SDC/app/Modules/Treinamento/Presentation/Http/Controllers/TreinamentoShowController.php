<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Application\DTOs\TreinamentoListDTO;
use App\Modules\Treinamento\Application\UseCases\Treinamento\ShowTreinamentoUseCase;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoShowController extends Controller
{
    public function __construct(
        private readonly ShowTreinamentoUseCase $showUseCase
    ) {
    }

    public function __invoke(int $id): Response
    {
        $treinamento = $this->showUseCase->execute($id);

        if (!$treinamento) {
            abort(404, 'Treinamento não encontrado');
        }

        return Inertia::render('Treinamento/TreinamentoShow', [
            'treinamento' => TreinamentoListDTO::fromEntity($treinamento)->toArray(),
        ]);
    }
}
