<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Resources\TreinamentoResource;
use App\Modules\Treinamento\Services\InscricaoService;
use App\Modules\Treinamento\Services\ProfessorService;
use App\Modules\Treinamento\Services\TreinamentoService;
use App\Modules\Treinamento\Services\Versoes\NovaVersaoDoCursoService;
use App\Modules\Treinamento\Support\VersoesDoCurso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoShowController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService,
        private readonly ProfessorService $professorService,
        private readonly InscricaoService $inscricaoService,
        private readonly NovaVersaoDoCursoService $versoes
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $treinamento = $this->treinamentoService->findById($id);

        if (!$treinamento) {
            abort(404, 'Treinamento nao encontrado');
        }

        $user = $request->user();
        $minhaInscricao = $treinamento->inscricoes
            ->first(fn ($i) => $i->inscrito_type === $user::class && $i->inscrito_id === $user->id);

        $rascunho = $treinamento->curso_id ? $this->versoes->rascunhoDe($treinamento->curso_id) : null;

        return Inertia::render('Treinamento/TreinamentoShow', [
            'treinamento' => (new TreinamentoResource($treinamento))->resolve(),
            'sexoPadrao' => $this->inscricaoService->sexoDaUltimaInscricao($user)?->value,
            'professores' => $treinamento->isEad() ? $this->professorService->listar($treinamento) : [],
            'versoes' => $this->versoesDoCurso($treinamento),
            'rascunho_id' => $rascunho?->id,
            'pode_criar_versao' => $treinamento->isEad()
                && $treinamento->publicado_em !== null
                && $rascunho === null
                && $treinamento->eVigente()
                && $user->can('treinamento.cursos.edit'),
            'minhaInscricao' => $minhaInscricao ? [
                'id' => $minhaInscricao->id,
                'status' => $minhaInscricao->status->value,
                'status_label' => $minhaInscricao->status->getLabel(),
                'certificado_disponivel' => $minhaInscricao->certificado?->status->value === 'GERADO',
                'certificado_id' => $minhaInscricao->certificado?->id,
            ] : null,
        ]);
    }

    /**
     * Versoes do curso (mais nova primeiro) para a lista da tela; so existe para EAD
     * vinculado ao catalogo.
     *
     * @return list<array<string, mixed>>
     */
    private function versoesDoCurso(Treinamento $treinamento): array
    {
        if (! $treinamento->isEad() || ! $treinamento->curso_id) {
            return [];
        }

        $vigenteId = VersoesDoCurso::vigenteDe($treinamento->curso_id)?->id;

        return VersoesDoCurso::listar($treinamento->curso_id)
            ->map(fn (Treinamento $v) => [
                'id' => $v->id,
                'versao' => $v->versao,
                'rotulo' => $v->rotuloDeVersao(),
                'publicada' => $v->publicado_em !== null,
                'publicada_em' => $v->publicado_em?->toIso8601String(),
                'vigente' => $v->id === $vigenteId,
                'inscritos' => (int) $v->inscritos,
                'formados' => (int) $v->formados,
                'atual' => $v->id === $treinamento->id,
            ])
            ->values()
            ->all();
    }
}
