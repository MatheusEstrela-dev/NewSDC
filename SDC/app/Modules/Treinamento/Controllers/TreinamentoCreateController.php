<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoCreateController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Treinamento/TreinamentoCreate', [
            'filterOptions' => [
                'tipos' => TipoTreinamento::toSelectArray(),
                'categorias' => CategoriaTreinamento::toSelectArray(),
            ],
        ]);
    }
}
