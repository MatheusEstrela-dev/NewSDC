<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Prova;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaQuestao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ProvaConteudoService;
use Illuminate\Http\Response;

class QuestaoDestroyController extends Controller
{
    public function __construct(private readonly ProvaConteudoService $conteudo) {}

    public function __invoke(Treinamento $treinamento, Prova $prova, ProvaQuestao $questao): Response
    {
        $this->conteudo->garantirDoCurso($treinamento, $prova);
        $this->conteudo->garantirQuestaoDaProva($prova, $questao);
        $this->conteudo->excluirQuestao($questao);

        return response()->noContent();
    }
}
