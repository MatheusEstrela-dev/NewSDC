<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Prova;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Resources\ProvaProfessorResource;
use App\Modules\Treinamento\Services\ProvaConteudoService;

class ProvaShowController extends Controller
{
    public function __construct(private readonly ProvaConteudoService $conteudo) {}

    public function __invoke(Treinamento $treinamento, Prova $prova): ProvaProfessorResource
    {
        $this->conteudo->garantirDoCurso($treinamento, $prova);

        return new ProvaProfessorResource($prova->load(['item', 'questoes.alternativas']));
    }
}
