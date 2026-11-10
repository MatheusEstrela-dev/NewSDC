<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ConteudoCursoService;

class PublicarController extends Controller
{
    public function __construct(private readonly ConteudoCursoService $conteudo) {}

    public function __invoke(Treinamento $treinamento)
    {
        try {
            $this->conteudo->publicar($treinamento);
        } catch (\DomainException $e) {
            return back()->withErrors(['publicar' => $e->getMessage()]);
        }

        return back()->with('success', 'Curso publicado.');
    }
}
