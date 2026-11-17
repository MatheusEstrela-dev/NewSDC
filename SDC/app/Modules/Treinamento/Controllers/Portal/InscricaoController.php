<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\InscreverRequest;
use App\Modules\Treinamento\Services\InscricaoService;
use Illuminate\Support\Facades\Auth;

class InscricaoController extends Controller
{
    public function __construct(
        private readonly InscricaoService $inscricaoService
    ) {
    }

    public function store(InscreverRequest $request, string $slug)
    {
        $treinamento = Treinamento::query()
            ->publicado()
            ->where('link_publico_slug', $slug)
            ->firstOrFail();

        try {
            $inscricao = $this->inscricaoService->inscrever($treinamento, Auth::guard('cidadao')->user(), $request->sexo());
        } catch (\DomainException $e) {
            return back()->withErrors(['inscricao' => $e->getMessage()]);
        }

        return back()->with('success', $this->inscricaoService->mensagemDeSucesso($inscricao));
    }
}
