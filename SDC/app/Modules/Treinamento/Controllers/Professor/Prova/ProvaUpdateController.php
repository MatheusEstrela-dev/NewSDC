<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Prova;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Professor\Prova\ConfigurarProvaRequest;
use App\Modules\Treinamento\Resources\ProvaProfessorResource;
use App\Modules\Treinamento\Services\ProvaConteudoService;

class ProvaUpdateController extends Controller
{
    public function __construct(private readonly ProvaConteudoService $conteudo) {}

    public function __invoke(ConfigurarProvaRequest $request, Treinamento $treinamento, Prova $prova): ProvaProfessorResource
    {
        $this->conteudo->garantirDoCurso($treinamento, $prova);
        $this->conteudo->configurar($prova, $request->validated());

        return new ProvaProfessorResource($prova->load(['item', 'questoes.alternativas']));
    }
}
