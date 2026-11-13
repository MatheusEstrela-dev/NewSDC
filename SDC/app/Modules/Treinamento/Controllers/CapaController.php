<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CapaDoCursoService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Entrega a imagem de capa do curso a quem ja ve a lista de cursos. */
class CapaController extends Controller
{
    public function __construct(private readonly CapaDoCursoService $capas) {}

    public function __invoke(Treinamento $treinamento): StreamedResponse
    {
        return $this->capas->responder($treinamento);
    }
}
