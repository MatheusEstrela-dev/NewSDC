<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use App\Modules\Treinamento\Support\ProvaDoAluno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaProvaIniciarController extends Controller
{
    public function __construct(private readonly ProvaDoAluno $prova) {}

    public function __invoke(Request $request, string $slug, int $item): JsonResponse
    {
        $treinamento = InscricaoDoAluno::cursoPublicado($slug);
        $inscricao = InscricaoDoAluno::aprovada($treinamento, Auth::guard('cidadao')->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        return $this->prova->iniciar($inscricao, $moduloItem);
    }
}
