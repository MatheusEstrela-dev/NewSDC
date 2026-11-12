<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoCreateController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Treinamento/TreinamentoCreate', [
            'filterOptions' => [
                'tipos' => TipoTreinamento::toSelectArray(),
                'categorias' => CategoriaTreinamento::toSelectArray(),
            ],
            'publicos' => PublicoAlvo::toSelectArray(),
            // "Abrir curso" chega aqui com ?categoria=CURSO&tipo=EAD; valor invalido e ignorado.
            'padroes' => (object) array_filter([
                'categoria' => CategoriaTreinamento::tryFrom((string) $request->query('categoria'))?->value,
                'tipo' => TipoTreinamento::tryFrom((string) $request->query('tipo'))?->value,
            ]),
        ]);
    }
}
