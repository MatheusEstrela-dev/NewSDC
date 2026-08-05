<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Requests\ReprovarInscricaoRequest;
use App\Modules\Treinamento\Services\InscricaoService;

class InscricaoReprovarController extends Controller
{
    public function __construct(
        private readonly InscricaoService $inscricaoService
    ) {
    }

    public function __invoke(ReprovarInscricaoRequest $request, Inscricao $inscricao)
    {
        try {
            $this->inscricaoService->reprovar($inscricao, $request->user(), $request->validated('observacoes'));
        } catch (\DomainException $e) {
            return back()->withErrors(['inscricao' => $e->getMessage()]);
        }

        return back()->with('success', 'Inscricao reprovada.');
    }
}
