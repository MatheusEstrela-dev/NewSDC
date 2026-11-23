<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\NotaAvaliacaoCurso;
use App\Modules\Treinamento\Enums\NotaRelevanciaCurso;
use App\Modules\Treinamento\Enums\SituacaoAvaliacaoCurso;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Resources\PortalInscricaoResource;
use App\Modules\Treinamento\Services\ConclusaoService;
use App\Modules\Treinamento\Services\TrilhaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MinhasInscricoesController extends Controller
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly ConclusaoService $conclusao,
    ) {}

    public function __invoke(): Response
    {
        /** @var Cidadao $cidadao */
        $cidadao = Auth::guard('cidadao')->user();

        $inscricoes = $cidadao->inscricoes()
            ->with(['treinamento', 'certificado', 'avaliacao'])
            ->orderBy('data_inscricao', 'desc')
            ->paginate(10);

        // Antes do Resource: ele troca os itens da pagina por wrappers.
        $salas = $this->salas($inscricoes->getCollection());
        $pendentes = $this->avaliacoesPendentes($inscricoes->getCollection(), $salas);

        return Inertia::render('Treinamento/Portal/MinhasInscricoes', [
            'inscricoes' => PortalInscricaoResource::collection($inscricoes),
            'salas' => $salas,
            'avaliacoes_pendentes' => $pendentes,
            'notas_avaliacao' => NotaAvaliacaoCurso::toSelectArray(),
            'notas_relevancia' => NotaRelevanciaCurso::toSelectArray(),
        ]);
    }

    /**
     * Percentual e link da sala das inscricoes EAD aprovadas, por id da
     * inscricao. O percentual conta a avaliacao do curso como etapa final, e
     * avaliacao_liberada diz se a trilha ja fechou e falta so avaliar.
     *
     * @param  Collection<int, Inscricao>  $inscricoes
     * @return array<int, array{percentual: float, sala_url: string, avaliacao_pendente: bool}>
     */
    private function salas(Collection $inscricoes): array
    {
        return $inscricoes
            ->filter(fn (Inscricao $i) => $i->status === StatusInscricao::APROVADA && $i->treinamento?->isEad() && $i->treinamento->situacaoEfetiva()->permiteAcessoDoAluno())
            ->mapWithKeys(function (Inscricao $i) {
                $estado = $this->trilha->estado($i);
                $avaliacao = SituacaoAvaliacaoCurso::de($i->avaliacao !== null, $this->conclusao->requisitosCumpridos($i, $estado));

                return [$i->id => [
                    'percentual' => $estado->percentualDoCurso($avaliacao === SituacaoAvaliacaoCurso::ENVIADA || $i->estaConcluida()),
                    'sala_url' => route('portal.treinamento.cursos.sala', $i->treinamento->link_publico_slug),
                    'avaliacao_pendente' => $avaliacao === SituacaoAvaliacaoCurso::LIBERADA && ! $i->estaConcluida(),
                ]];
            })
            ->all();
    }

    /**
     * Eventos (presencial/online/semi, sem sala) da pagina em que o cidadao ja
     * cumpriu os requisitos e falta so avaliar para concluir, por id da
     * inscricao. O EAD pendente avalia na sala (salas[].avaliacao_pendente).
     *
     * @param  Collection<int, Inscricao>  $inscricoes
     * @param  array<int, mixed>  $salas
     * @return array<int, array{url_avaliar: string}>
     */
    private function avaliacoesPendentes(Collection $inscricoes, array $salas): array
    {
        return $inscricoes
            ->filter(fn (Inscricao $i) => ! isset($salas[$i->id])
                && ! $i->treinamento?->isEad()
                && $i->treinamento?->status === StatusTreinamento::CONCLUIDO
                && $i->treinamento->estaPublicado()
                && $i->concluido_em === null
                && $i->avaliacao === null
                && $this->conclusao->requisitosCumpridos($i))
            ->mapWithKeys(fn (Inscricao $i) => [$i->id => [
                'url_avaliar' => route('portal.treinamento.cursos.avaliacao', $i->treinamento->link_publico_slug),
            ]])
            ->all();
    }
}
