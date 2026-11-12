<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\ReavaliacaoDeConclusaoService;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use App\Modules\Treinamento\Support\SalaDeAulaProps;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SalaDeAulaController extends Controller
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly ReavaliacaoDeConclusaoService $reavaliacao,
    ) {}

    public function __invoke(string $slug): Response
    {
        $treinamento = InscricaoDoAluno::cursoPublicado($slug);
        $inscricao = InscricaoDoAluno::aprovada($treinamento, Auth::guard('cidadao')->user());
        $this->reavaliacao->avaliarSemDerrubar($inscricao);

        return Inertia::render(
            'Treinamento/Portal/SalaDeAula',
            SalaDeAulaProps::de($treinamento, $this->trilha->estado($inscricao), 'portal'),
        );
    }
}
