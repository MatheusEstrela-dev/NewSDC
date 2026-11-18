<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Versoes\AtualizacaoDeVersaoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Quem se formou em versao anterior entra na vigente aproveitando o que nao mudou.
 */
class AtualizarVersaoController extends Controller
{
    public function __construct(private readonly AtualizacaoDeVersaoService $atualizacao) {}

    public function __invoke(Request $request, Treinamento $treinamento): RedirectResponse
    {
        try {
            $this->atualizacao->atualizar($treinamento, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('treinamentos.aluno.sala', $treinamento->id)
            ->with('success', "Você foi atualizado para a versão {$treinamento->rotuloDeVersao()} do curso.");
    }
}
