<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Requests\AprovarInscricaoRequest;
use App\Modules\Treinamento\Services\InscricaoService;

class InscricaoAprovarController extends Controller
{
    public function __construct(
        private readonly InscricaoService $inscricaoService
    ) {
    }

    public function __invoke(AprovarInscricaoRequest $request, Inscricao $inscricao)
    {
        try {
            $this->inscricaoService->aprovar($inscricao, $request->user(), $request->validated('observacoes'));
        } catch (\DomainException $e) {
            return back()->withErrors(['inscricao' => $e->getMessage()]);
        }

        return back()->with('success', 'Inscricao aprovada.');
    }
}
