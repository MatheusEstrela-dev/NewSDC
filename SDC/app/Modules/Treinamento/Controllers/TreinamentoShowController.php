<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Resources\TreinamentoResource;
use App\Modules\Treinamento\Services\InscricaoService;
use App\Modules\Treinamento\Services\ProfessorService;
use App\Modules\Treinamento\Services\TreinamentoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoShowController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService,
        private readonly ProfessorService $professorService,
        private readonly InscricaoService $inscricaoService
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

        return Inertia::render('Treinamento/TreinamentoShow', [
            'treinamento' => (new TreinamentoResource($treinamento))->resolve(),
            'sexoPadrao' => $this->inscricaoService->sexoDaUltimaInscricao($user)?->value,
            'professores' => $treinamento->isEad() ? $this->professorService->listar($treinamento) : [],
            // Turmas abertas, so o que a tela de inscricao precisa para deixar
            // escolher uma (nome, periodo, vagas restantes). Curso sem nenhuma
            // turma manda lista vazia e a inscricao segue sem turma.
            'turmas' => $this->turmasParaInscricao($treinamento),
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
     * Turmas do curso para o select da inscricao: so o que a tela precisa
     * (nao reaproveita GestaoCursoConsulta::turmas(), que e do gestor e
     * mostraria inscricoes_pendentes a quem so esta se inscrevendo).
     *
     * @return list<array{id: int, nome: string, data_inicio: string, data_fim: string, local: string|null, vagas_restantes: int, encerrou_inscricoes: bool}>
     */
    private function turmasParaInscricao(Treinamento $treinamento): array
    {
        return $treinamento->turmas()
            ->get()
            ->map(fn ($turma) => [
                'id' => (int) $turma->getKey(),
                'nome' => (string) $turma->nome,
                'data_inicio' => $turma->data_inicio?->toDateString(),
                'data_fim' => $turma->data_fim?->toDateString(),
                'local' => $turma->local,
                'vagas_restantes' => $turma->vagasRestantes(),
                'encerrou_inscricoes' => $turma->encerrouInscricoes(),
            ])
            ->values()
            ->all();
    }
}
