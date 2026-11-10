<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use App\Modules\Treinamento\Support\SalaDeAulaProps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalaDeAulaController extends Controller
{
    public function __construct(private readonly TrilhaService $trilha) {}

    public function __invoke(Request $request, Treinamento $treinamento): Response
    {
        $inscricao = InscricaoDoAluno::aprovada($treinamento, $request->user());

        return Inertia::render(
            'Treinamento/Aluno/SalaDeAula',
            SalaDeAulaProps::de($treinamento, $this->trilha->estado($inscricao), 'aluno'),
        );
    }
}
