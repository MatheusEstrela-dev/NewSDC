<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\AcompanhamentoService;
use Inertia\Inertia;
use Inertia\Response;

class CorrecoesController extends Controller
{
    public function __construct(private readonly AcompanhamentoService $acompanhamento) {}

    public function __invoke(Treinamento $treinamento): Response
    {
        $entregas = $this->acompanhamento->pendentes($treinamento);

        return Inertia::render('Treinamento/Professor/Correcoes', [
            'curso' => ['id' => $treinamento->id, 'titulo' => $treinamento->titulo, 'publicado' => $treinamento->estaPublicado()],
            'entregas' => $entregas,
            'pendentes' => $entregas->count(),
        ]);
    }
}
