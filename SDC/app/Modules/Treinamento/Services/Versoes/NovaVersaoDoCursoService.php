<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Versoes;

use App\Models\User;
use App\Modules\Treinamento\Enums\SituacaoCurso;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoEventoCurso;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Curso;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\TreinamentoProfessor;
use App\Modules\Treinamento\Services\CatalogoService;
use App\Modules\Treinamento\Services\HistoricoDoCursoService;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use DomainException;

/**
 * Cria a proxima versao (rascunho) de um curso EAD copiando o conteudo da vigente.
 * Inscricoes, progresso, tentativas, entregas, frequencias, certificados, cronogramas
 * e historico NAO sao copiados: pertencem a versao em que aconteceram.
 */
class NovaVersaoDoCursoService
{
    private const CAMPOS_COPIADOS = [
        'titulo', 'descricao', 'carga_horaria', 'categoria', 'tipo', 'publico_alvo',
        'inscricao_aprovacao_automatica', 'status', 'instrutor', 'local', 'municipio_id',
        'data_inicio', 'data_fim', 'hora_inicio', 'numero_vagas', 'percentual_frequencia_minimo',
    ];

    public function __construct(
        private readonly CopiaDeConteudo $copia,
        private readonly CatalogoService $catalogo,
        private readonly HistoricoDoCursoService $historico,
    ) {}

    /** Rascunho (versao nao publicada) em andamento do curso do catalogo, se houver. */
    public function rascunhoDe(int $cursoId): ?Treinamento
    {
        return Treinamento::query()
            ->where('curso_id', $cursoId)
            ->where('tipo', TipoTreinamento::EAD->value)
            ->whereNull('publicado_em')
            ->orderByDesc('versao')
            ->first();
    }

    public function criar(Treinamento $vigente, User $autor): Treinamento
    {
        if ($vigente->tipo !== TipoTreinamento::EAD) {
            throw new DomainException('So cursos EAD podem ter nova versao.');
        }
        if ($vigente->publicado_em === null) {
            throw new DomainException('So um curso publicado pode ter nova versao.');
        }

        return ConexaoTreinamento::transacao(function () use ($vigente, $autor): Treinamento {
            $curso = $this->cursoTravado($vigente, $autor);

            $vigente->refresh();
            if (! $vigente->eVigente()) {
                throw new DomainException('Somente a versao vigente do curso pode gerar uma nova versao.');
            }

            $existente = $this->rascunhoDe($curso->id);
            if ($existente !== null) {
                throw new DomainException("Ja existe um rascunho da versao {$existente->versao} deste curso.");
            }

            $rascunho = $this->criarRascunho($vigente, $curso, $autor);
            $this->copia->copiar($vigente, $rascunho);
            $this->copiarProfessores($vigente, $rascunho);

            $this->historico->registrar(
                $rascunho->id,
                TipoEventoCurso::VERSAO_CRIADA,
                "Versao {$rascunho->rotuloDeVersao()} criada a partir da {$vigente->rotuloDeVersao()}.",
                ['versao_origem_id' => $vigente->id, 'versao' => $rascunho->versao],
                $autor->id,
            );

            return $rascunho;
        });
    }

    /** Curso do catalogo da turma (criado/vinculado se faltar), com a linha travada ate o fim da transacao. */
    private function cursoTravado(Treinamento $vigente, User $autor): Curso
    {
        $cursoId = $vigente->curso_id ?? $this->vincularAoCatalogo($vigente, $autor)->id;

        return Curso::query()->lockForUpdate()->findOrFail($cursoId);
    }

    private function vincularAoCatalogo(Treinamento $vigente, User $autor): Curso
    {
        $chave = CatalogoService::normalizarNome($vigente->titulo);

        $curso = Curso::query()->get(['id', 'nome'])
            ->first(fn (Curso $c) => CatalogoService::normalizarNome($c->nome) === $chave)
            ?? $this->catalogo->criar(['nome' => $vigente->titulo], $autor->id);

        $vigente->update(['curso_id' => $curso->id]);

        return $curso;
    }

    private function criarRascunho(Treinamento $vigente, Curso $curso, User $autor): Treinamento
    {
        $proxima = (int) Treinamento::withTrashed()->where('curso_id', $curso->id)->max('versao') + 1;

        $rascunho = new Treinamento($vigente->only(self::CAMPOS_COPIADOS));
        $rascunho->forceFill([
            'capa_path' => $vigente->capa_path,
            'curso_id' => $curso->id,
            'versao' => $proxima,
            'versao_origem_id' => $vigente->id,
            'publicado_em' => null,
            'link_publico_slug' => null,
            'situacao' => SituacaoCurso::ATIVO->value,
            'status' => $this->statusInicial($vigente),
            'created_by' => $autor->id,
        ])->save();

        return $rascunho;
    }

    /** Status copiado so se ainda recebe inscricao; senao o rascunho nasceria impublicavel. */
    private function statusInicial(Treinamento $vigente): string
    {
        return ($vigente->status->podeReceberInscricoes() ? $vigente->status : StatusTreinamento::PLANEJADO)->value;
    }

    private function copiarProfessores(Treinamento $origem, Treinamento $destino): void
    {
        TreinamentoProfessor::query()
            ->where('treinamento_id', $origem->id)
            ->pluck('user_id')
            ->each(fn ($userId) => TreinamentoProfessor::query()->create([
                'treinamento_id' => $destino->id,
                'user_id' => $userId,
            ]));
    }
}
