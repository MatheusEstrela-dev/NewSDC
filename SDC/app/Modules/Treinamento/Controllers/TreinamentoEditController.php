<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Resources\TreinamentoResource;
use App\Modules\Treinamento\Services\TreinamentoService;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoEditController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(int $id): Response
    {
        $treinamento = $this->treinamentoService->findById($id);

        if (!$treinamento) {
            abort(404, 'Treinamento nao encontrado');
        }

        return Inertia::render('Treinamento/TreinamentoEdit', [
            'treinamento' => (new TreinamentoResource($treinamento))->resolve(),
            'filterOptions' => [
                'tipos' => TipoTreinamento::toSelectArray(),
                'categorias' => CategoriaTreinamento::toSelectArray(),
            ],
        ]);
    }
}
