<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use App\Modules\Treinamento\Support\ProvaDoAluno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvaIniciarController extends Controller
{
    public function __construct(private readonly ProvaDoAluno $prova) {}

    public function __invoke(Request $request, Treinamento $treinamento, int $item): JsonResponse
    {
        $inscricao = InscricaoDoAluno::aprovada($treinamento, $request->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        return $this->prova->iniciar($inscricao, $moduloItem);
    }
}
