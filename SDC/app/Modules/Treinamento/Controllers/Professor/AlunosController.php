<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\AcompanhamentoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlunosController extends Controller
{
    public function __construct(private readonly AcompanhamentoService $acompanhamento) {}

    public function __invoke(Request $request, Treinamento $treinamento): Response
    {
        $soTravados = $request->boolean('travados');

        return Inertia::render('Treinamento/Professor/Alunos', [
            'curso' => ['id' => $treinamento->id, 'titulo' => $treinamento->titulo, 'publicado' => $treinamento->estaPublicado()],
            'alunos' => $this->acompanhamento->alunos($treinamento, $soTravados),
            'travados' => $soTravados,
            'pendentes' => $this->acompanhamento->contarPendentes($treinamento),
        ]);
    }
}
