<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ConteudoCursoService;

class ItemDestroyController extends Controller
{
    public function __construct(private readonly ConteudoCursoService $conteudo) {}

    public function __invoke(Treinamento $treinamento, ModuloItem $item)
    {
        $this->conteudo->garantirDoCurso($treinamento, $item);
        $this->conteudo->excluirItem($item);

        return back()->with('success', 'Item excluido.');
    }
}
