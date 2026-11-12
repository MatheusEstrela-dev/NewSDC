<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Prova;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Professor\Prova\SalvarQuestaoRequest;
use App\Modules\Treinamento\Resources\ProvaProfessorResource;
use App\Modules\Treinamento\Services\ProvaConteudoService;
use Illuminate\Http\JsonResponse;

class QuestaoStoreController extends Controller
{
    public function __construct(private readonly ProvaConteudoService $conteudo) {}

    public function __invoke(SalvarQuestaoRequest $request, Treinamento $treinamento, Prova $prova): JsonResponse
    {
        $this->conteudo->garantirDoCurso($treinamento, $prova);
        $questao = $this->conteudo->criarQuestao($prova, $request->validated());

        return response()->json(ProvaProfessorResource::questao($questao->load('alternativas')), 201);
    }
}
