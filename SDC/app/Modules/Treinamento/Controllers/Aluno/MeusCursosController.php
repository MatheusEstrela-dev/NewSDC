<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\TreinamentoProfessor;
use App\Modules\Treinamento\Resources\CertificadoResource;
use App\Modules\Treinamento\Services\TrilhaService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MeusCursosController extends Controller
{
    public function __construct(private readonly TrilhaService $trilha) {}

    public function __invoke(Request $request): Response
    {
        $userId = (int) $request->user()->getKey();

        $inscricoes = Inscricao::query()
            ->with('treinamento')
            ->where('inscrito_type', (new User)->getMorphClass())
            ->where('inscrito_id', $userId)
            ->whereHas('treinamento', fn ($q) => $q->where('tipo', TipoTreinamento::EAD->value))
            ->orderByDesc('data_inscricao')
            ->get();

        return Inertia::render('Treinamento/Aluno/MeusCursos', [
            'meus_cursos' => $inscricoes->map(fn (Inscricao $i) => $this->curso($i))->values(),
            'certificados' => CertificadoResource::collection($this->certificados($inscricoes->pluck('id')->all()))->resolve(),
            'disponiveis' => $this->disponiveis($userId, $inscricoes->pluck('treinamento_id')->all()),
        ]);
    }

    /**
     * Certificados do proprio servidor, dos mais recentes aos mais antigos.
     *
     * @param  list<int>  $inscricaoIds
     * @return Collection<int, Certificado>
     */
    private function certificados(array $inscricaoIds): Collection
    {
        return Certificado::query()
            ->whereIn('inscricao_id', $inscricaoIds)
            ->with('treinamento')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function curso(Inscricao $inscricao): array
    {
        $aprovada = $inscricao->status === StatusInscricao::APROVADA;

        return [
            'id' => $inscricao->treinamento_id,
            'titulo' => $inscricao->treinamento->titulo,
            'carga_horaria' => $inscricao->treinamento->carga_horaria,
            'descricao' => $inscricao->treinamento->descricao,
            'instrutor' => $inscricao->treinamento->instrutor,
            'status_label' => $inscricao->status->getLabel(),
            'aprovada' => $aprovada,
            'percentual' => $aprovada ? $this->trilha->estado($inscricao)->percentual : 0,
            'sala_url' => $aprovada ? route('treinamentos.aluno.sala', $inscricao->treinamento_id) : null,
        ];
    }

    /**
     * Cursos EAD de servidores abertos a inscricao em que o usuario ainda nao
     * esta inscrito nem leciona.
     *
     * @param  list<int>  $inscritos
     * @return list<array<string, mixed>>
     */
    private function disponiveis(int $userId, array $inscritos): array
    {
        $leciona = TreinamentoProfessor::query()->where('user_id', $userId)->pluck('treinamento_id')->all();

        return Treinamento::query()
            ->where('tipo', TipoTreinamento::EAD->value)
            ->paraPublico(PublicoAlvo::SERVIDORES)
            ->publicado()
            ->whereNotIn('id', array_merge($inscritos, $leciona))
            ->orderBy('titulo')
            ->get()
            ->filter(fn (Treinamento $t) => $t->podeReceberInscricao())
            ->map(fn (Treinamento $t) => [
                'id' => $t->id,
                'titulo' => $t->titulo,
                'carga_horaria' => $t->carga_horaria,
                'descricao' => $t->descricao,
                'instrutor' => $t->instrutor,
            ])
            ->values()
            ->all();
    }
}
