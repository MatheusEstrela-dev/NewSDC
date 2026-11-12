<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor\Prova;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\AcompanhamentoService;
use App\Modules\Treinamento\Services\ProvasDoCursoService;
use Inertia\Inertia;
use Inertia\Response;

class ProvasController extends Controller
{
    public function __construct(
        private readonly ProvasDoCursoService $provas,
        private readonly AcompanhamentoService $acompanhamento,
    ) {}

    public function __invoke(Treinamento $treinamento): Response
    {
        return Inertia::render('Treinamento/Professor/Provas', [
            'curso' => ['id' => $treinamento->id, 'titulo' => $treinamento->titulo, 'publicado' => $treinamento->estaPublicado()],
            'provas' => $this->provas->resumo($treinamento),
            'pendentes' => $this->acompanhamento->contarPendentes($treinamento),
        ]);
    }
}
