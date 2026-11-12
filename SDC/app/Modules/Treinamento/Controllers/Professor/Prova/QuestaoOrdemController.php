<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Prova;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Professor\ReordenarRequest;
use App\Modules\Treinamento\Services\ProvaConteudoService;
use Illuminate\Http\Response;

class QuestaoOrdemController extends Controller
{
    public function __construct(private readonly ProvaConteudoService $conteudo) {}

    public function __invoke(ReordenarRequest $request, Treinamento $treinamento, Prova $prova): Response
    {
        $this->conteudo->garantirDoCurso($treinamento, $prova);
        $this->conteudo->reordenarQuestoes($prova, $request->validated('ids'));

        return response()->noContent();
    }
}
