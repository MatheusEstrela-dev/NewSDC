<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Treinamento\Enums\NotaAvaliacaoCurso;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Enums\SituacaoAvaliacaoCurso;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\TreinamentoProfessor;
use App\Modules\Treinamento\Resources\CertificadoResource;
use App\Modules\Treinamento\Services\ConclusaoService;
use App\Modules\Treinamento\Services\CredencialQrService;
use App\Modules\Treinamento\Services\InscricaoService;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\NovasVersoesDoAluno;
use App\Modules\Treinamento\Support\VersoesDoCurso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MeusCursosController extends Controller
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly InscricaoService $inscricaoService,
        private readonly CredencialQrService $credenciais,
        private readonly ConclusaoService $conclusao,
    ) {}

    public function __invoke(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();

        $inscricoes = $this->doServidor($userId)
            ->with(['treinamento', 'avaliacao'])
            ->whereHas('treinamento', fn ($q) => $q->where('tipo', TipoTreinamento::EAD->value))
            ->orderByDesc('data_inscricao')
            ->get();

        return Inertia::render('Treinamento/Aluno/MeusCursos', [
            'meus_cursos' => $this->cursos($inscricoes),
            'avaliacoes_pendentes' => $this->avaliacoesPendentes($userId),
            'notas_avaliacao' => NotaAvaliacaoCurso::toSelectArray(),
            'certificados' => CertificadoResource::collection($this->certificados($userId))->resolve(),
            'credencial' => $request->user() instanceof User ? $this->credenciais->paraServidor($request->user()) : null,
            'sexo_padrao' => $this->inscricaoService->sexoDaUltimaInscricao($request->user())?->value,
            'disponiveis' => $this->disponiveis($userId, $inscricoes),
        ]);
    }

    /** Inscricoes do proprio servidor (a inscricao nunca vem da URL). */
    private function doServidor(int $userId): Builder
    {
        return Inscricao::query()
            ->where('inscrito_type', (new User)->getMorphClass())
            ->where('inscrito_id', $userId);
    }

    /**
     * @param  Collection<int, Inscricao>  $inscricoes
     * @return Collection<int, array<string, mixed>>
     */
    private function cursos(Collection $inscricoes): Collection
    {
        $rotulos = VersoesDoCurso::rotulos($inscricoes->pluck('treinamento'));
        $novas = NovasVersoesDoAluno::para($inscricoes);

        return $inscricoes->map(fn (Inscricao $i) => $this->curso($i) + [
            'versao_rotulo' => $rotulos[$i->treinamento_id] ?? null,
            'nova_versao' => isset($novas[$i->id])
                ? $novas[$i->id] + ['atualizar_url' => route('treinamentos.aluno.atualizar', $novas[$i->id]['id'])]
                : null,
        ])->values();
    }

    /**
     * Certificados do proprio servidor, de qualquer tipo de curso (o dos
     * eventos sai ao avaliar aqui mesmo), dos mais recentes aos mais antigos.
     *
     * @return Collection<int, Certificado>
     */
    private function certificados(int $userId): Collection
    {
        return Certificado::query()
            ->whereIn('inscricao_id', $this->doServidor($userId)->select('id'))
            ->with('treinamento')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Eventos (presencial/online/semi, sem sala de aula) em que o servidor ja
     * cumpriu os requisitos e falta so avaliar para concluir. O EAD avalia na
     * propria sala (etapa final da trilha). O filtro em SQL corta o grosso;
     * a frequencia minima e conferida pelo mesmo criterio da conclusao.
     *
     * @return list<array<string, mixed>>
     */
    private function avaliacoesPendentes(int $userId): array
    {
        return $this->doServidor($userId)
            ->aprovadas()
            ->whereNull('concluido_em')
            ->whereDoesntHave('avaliacao')
            ->whereHas('treinamento', fn ($q) => $q->where('tipo', '!=', TipoTreinamento::EAD->value)
                ->where('status', StatusTreinamento::CONCLUIDO->value))
            ->with('treinamento')
            ->orderByDesc('data_inscricao')
            ->get()
            ->filter(fn (Inscricao $i) => $this->conclusao->requisitosCumpridos($i))
            ->map(fn (Inscricao $i) => [
                'inscricao_id' => $i->id,
                'treinamento_id' => $i->treinamento_id,
                'titulo' => $i->treinamento->titulo,
                'tipo_label' => $i->treinamento->tipo->getLabel(),
                'carga_horaria' => $i->treinamento->carga_horaria,
                'url_avaliar' => route('treinamentos.aluno.avaliacao', $i->treinamento_id),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function curso(Inscricao $inscricao): array
    {
        $aprovada = $inscricao->status === StatusInscricao::APROVADA;
        $situacao = $inscricao->treinamento->situacaoEfetiva();
        $estado = $aprovada ? $this->trilha->estado($inscricao) : null;
        $avaliacao = $estado === null ? null : SituacaoAvaliacaoCurso::de(
            $inscricao->avaliacao !== null,
            $this->conclusao->requisitosCumpridos($inscricao, $estado),
        );

        return [
            'id' => $inscricao->treinamento_id,
            'titulo' => $inscricao->treinamento->titulo,
            'carga_horaria' => $inscricao->treinamento->carga_horaria,
            'descricao' => $inscricao->treinamento->descricao,
            'instrutor' => $inscricao->treinamento->instrutor,
            'capa_url' => $inscricao->treinamento->capaUrl(),
            'status_label' => $inscricao->status->getLabel(),
            'aprovada' => $aprovada,
            // A avaliacao conta como etapa: trilha completa sem avaliar nao chega a 100%.
            'percentual' => $estado?->percentualDoCurso($avaliacao === SituacaoAvaliacaoCurso::ENVIADA || $inscricao->estaConcluida()) ?? 0,
            'concluido' => $inscricao->estaConcluida(),
            'avaliacao_pendente' => $avaliacao === SituacaoAvaliacaoCurso::LIBERADA && ! $inscricao->estaConcluida(),
            'sala_url' => $aprovada && $situacao->permiteAcessoDoAluno() ? route('treinamentos.aluno.sala', $inscricao->treinamento_id) : null,
            'situacao' => $situacao->value,
            'situacao_label' => $situacao->getLabel(),
            'situacao_variant' => $situacao->getVariant(),
        ];
    }

    /**
     * Cursos EAD de servidores abertos a inscricao em que o usuario ainda nao
     * esta inscrito nem leciona. A versao vigente de um curso que a pessoa ja
     * concluiu numa versao anterior nao entra: ali ela usa "Atualizar".
     *
     * @param  Collection<int, Inscricao>  $inscricoes  inscricoes EAD do usuario, com `treinamento`
     * @return list<array<string, mixed>>
     */
    private function disponiveis(int $userId, Collection $inscricoes): array
    {
        $inscritos = $inscricoes->pluck('treinamento_id')->all();
        $formadoEm = $inscricoes
            ->filter(fn (Inscricao $i) => $i->concluido_em !== null && $i->treinamento->curso_id !== null)
            ->map(fn (Inscricao $i) => $i->treinamento->curso_id)
            ->unique()
            ->all();
        $leciona = TreinamentoProfessor::query()->where('user_id', $userId)->pluck('treinamento_id')->all();

        return Treinamento::query()
            ->where('tipo', TipoTreinamento::EAD->value)
            ->paraPublico(PublicoAlvo::SERVIDORES)
            ->noCatalogo()
            ->whereNotIn('id', array_merge($inscritos, $leciona))
            ->when($formadoEm !== [], fn ($q) => $q->where(fn ($w) => $w->whereNull('curso_id')->orWhereNotIn('curso_id', $formadoEm)))
            ->orderBy('titulo')
            ->get()
            ->filter(fn (Treinamento $t) => $t->aceitaInscricaoAlemDaSituacao())
            ->map(fn (Treinamento $t) => [
                'id' => $t->id,
                'titulo' => $t->titulo,
                'carga_horaria' => $t->carga_horaria,
                'descricao' => $t->descricao,
                'instrutor' => $t->instrutor,
                'capa_url' => $t->capaUrl(),
                'situacao' => $t->situacaoEfetiva()->value,
                'situacao_label' => $t->situacaoEfetiva()->getLabel(),
                'situacao_variant' => $t->situacaoEfetiva()->getVariant(),
                'pausado' => ! $t->situacaoEfetiva()->recebeInscricao(),
            ])
            ->values()
            ->all();
    }
}
