<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\EnviarEntregaRequest;
use App\Modules\Treinamento\Services\ProjetoService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SalaEntregaStoreController extends Controller
{
    public function __construct(private readonly ProjetoService $projetos) {}

    public function __invoke(EnviarEntregaRequest $request, string $slug, int $item): RedirectResponse
    {
        $treinamento = InscricaoDoAluno::cursoPublicado($slug);
        $inscricao = InscricaoDoAluno::aprovada($treinamento, Auth::guard('cidadao')->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        try {
            $this->projetos->enviar($inscricao, $moduloItem, $request->file('arquivo'), $request->input('comentario'));
        } catch (\DomainException $e) {
            return back()->withErrors(['arquivo' => $e->getMessage()]);
        }

        return back()->with('success', 'Projeto enviado para correcao.');
    }
}
