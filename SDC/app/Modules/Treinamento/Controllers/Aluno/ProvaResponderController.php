<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\ResponderProvaRequest;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use App\Modules\Treinamento\Support\ProvaDoAluno;
use Illuminate\Http\JsonResponse;

class ProvaResponderController extends Controller
{
    public function __construct(private readonly ProvaDoAluno $prova) {}

    public function __invoke(ResponderProvaRequest $request, Treinamento $treinamento, int $item, int $tentativa): JsonResponse
    {
        $inscricao = InscricaoDoAluno::aprovada($treinamento, $request->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        return $this->prova->responder($inscricao, $moduloItem, $tentativa, $request->integer('questao_id'), $request->integer('alternativa_id'));
    }
}
