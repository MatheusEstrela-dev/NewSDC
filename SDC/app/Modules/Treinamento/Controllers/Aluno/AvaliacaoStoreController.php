<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\AvaliarCursoRequest;
use App\Modules\Treinamento\Services\AvaliacaoCursoService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use DomainException;
use Illuminate\Http\RedirectResponse;

/** Avaliacao do curso (etapa final, qualquer tipo) pelo servidor: conclui o curso e emite o certificado. */
class AvaliacaoStoreController extends Controller
{
    public function __construct(private readonly AvaliacaoCursoService $avaliacoes) {}

    public function __invoke(AvaliarCursoRequest $request, Treinamento $treinamento): RedirectResponse
    {
        $inscricao = InscricaoDoAluno::paraAvaliar($treinamento, $request->user());

        try {
            $this->avaliacoes->avaliar($inscricao, $request->nota(), $request->comentario());
        } catch (DomainException $e) {
            return back()->withErrors(['nota' => $e->getMessage()]);
        }

        return back()->with('success', 'Obrigado pela avaliação! Curso concluído.');
    }
}
