<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Video;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ConteudoCursoService;
use App\Modules\Treinamento\Services\Video\VideoBibliotecaService;
use Illuminate\Http\Response;

class ItemVideoDesvincularController extends Controller
{
    public function __construct(
        private readonly ConteudoCursoService $conteudo,
        private readonly VideoBibliotecaService $biblioteca,
    ) {}

    public function __invoke(Treinamento $treinamento, ModuloItem $item): Response
    {
        $this->conteudo->garantirDoCurso($treinamento, $item);
        $this->biblioteca->desvincular($item);

        return response()->noContent();
    }
}
