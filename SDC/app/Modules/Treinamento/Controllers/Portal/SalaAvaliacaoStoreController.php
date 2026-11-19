<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\AvaliarCursoRequest;
use App\Modules\Treinamento\Services\AvaliacaoCursoService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/** Avaliacao do curso (etapa final, qualquer tipo) pelo cidadao: conclui o curso e emite o certificado. */
class SalaAvaliacaoStoreController extends Controller
{
    public function __construct(private readonly AvaliacaoCursoService $avaliacoes) {}

    public function __invoke(AvaliarCursoRequest $request, string $slug): RedirectResponse
    {
        $treinamento = InscricaoDoAluno::cursoPublicado($slug);
        $inscricao = InscricaoDoAluno::paraAvaliar($treinamento, Auth::guard('cidadao')->user());

        try {
            $this->avaliacoes->avaliar($inscricao, $request->nota(), $request->comentario());
        } catch (DomainException $e) {
            return back()->withErrors(['nota' => $e->getMessage()]);
        }

        return back()->with('success', 'Obrigado pela avaliação! Curso concluído.');
    }
}
