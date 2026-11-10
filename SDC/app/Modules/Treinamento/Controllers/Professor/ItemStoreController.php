<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Professor\SalvarItemRequest;
use App\Modules\Treinamento\Services\ConteudoCursoService;

class ItemStoreController extends Controller
{
    public function __construct(private readonly ConteudoCursoService $conteudo) {}

    public function __invoke(SalvarItemRequest $request, Treinamento $treinamento, Modulo $modulo)
    {
        $this->conteudo->garantirDoCurso($treinamento, $modulo);

        try {
            $this->conteudo->criarItem($modulo, $request->safe()->except('arquivo'), $request->file('arquivo'));
        } catch (\DomainException $e) {
            return back()->withErrors(['item' => $e->getMessage()]);
        }

        return back()->with('success', 'Item adicionado.');
    }
}
