<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\EnviarEntregaRequest;
use App\Modules\Treinamento\Services\ProjetoService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use Illuminate\Http\RedirectResponse;

class EntregaStoreController extends Controller
{
    public function __construct(private readonly ProjetoService $projetos) {}

    public function __invoke(EnviarEntregaRequest $request, Treinamento $treinamento, int $item): RedirectResponse
    {
        $inscricao = InscricaoDoAluno::aprovada($treinamento, $request->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        try {
            $this->projetos->enviar($inscricao, $moduloItem, $request->file('arquivo'), $request->input('comentario'));
        } catch (\DomainException $e) {
            return back()->withErrors(['arquivo' => $e->getMessage()]);
        }

        return back()->with('success', 'Projeto enviado para correcao.');
    }
}
