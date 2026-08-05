<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\InscricaoService;
use Illuminate\Support\Facades\Auth;

class InscricaoController extends Controller
{
    public function __construct(
        private readonly InscricaoService $inscricaoService
    ) {
    }

    public function store(string $slug)
    {
        $treinamento = Treinamento::query()
            ->publicado()
            ->where('link_publico_slug', $slug)
            ->firstOrFail();

        try {
            $this->inscricaoService->inscrever($treinamento, Auth::guard('cidadao')->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['inscricao' => $e->getMessage()]);
        }

        return back()->with('success', 'Inscricao realizada com sucesso! Aguarde a aprovacao.');
    }
}
