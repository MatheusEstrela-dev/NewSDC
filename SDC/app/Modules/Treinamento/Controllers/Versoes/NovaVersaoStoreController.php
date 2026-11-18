<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Versoes;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Versoes\NovaVersaoDoCursoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Cria o rascunho da proxima versao e leva o gestor para a edicao do conteudo dele. */
class NovaVersaoStoreController extends Controller
{
    public function __construct(private readonly NovaVersaoDoCursoService $versoes) {}

    public function __invoke(Request $request, Treinamento $treinamento): RedirectResponse
    {
        try {
            $rascunho = $this->versoes->criar($treinamento, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['versao' => $e->getMessage()]);
        }

        return redirect()
            ->route('treinamentos.professor.conteudo', $rascunho->id)
            ->with('success', "Versão {$rascunho->rotuloDeVersao()} criada. Edite o conteúdo e publique quando estiver pronta.");
    }
}
