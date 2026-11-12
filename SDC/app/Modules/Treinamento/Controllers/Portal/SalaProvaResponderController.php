<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\ResponderProvaRequest;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use App\Modules\Treinamento\Support\ProvaDoAluno;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SalaProvaResponderController extends Controller
{
    public function __construct(private readonly ProvaDoAluno $prova) {}

    public function __invoke(ResponderProvaRequest $request, string $slug, int $item, int $tentativa): JsonResponse
    {
        $treinamento = InscricaoDoAluno::cursoPublicado($slug);
        $inscricao = InscricaoDoAluno::aprovada($treinamento, Auth::guard('cidadao')->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        return $this->prova->responder($inscricao, $moduloItem, $tentativa, $request->integer('questao_id'), $request->integer('alternativa_id'));
    }
}
