<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Treinamento\Enums\SituacaoCurso;
use App\Modules\Treinamento\Jobs\ReavaliarConclusoesDoCursoJob;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Video\AutorizacaoVideoService;
use App\Modules\Treinamento\Support\AvisoDeCursos;
use Throwable;

/**
 * Ciclo de vida do curso EAD: pausar/retomar (suspensao temporaria) e
 * desativar/reativar (encerramento reversivel). So muda a situacao, quem e
 * quando: publicacao, inscricoes, progresso, entregas e certificados nao sao
 * tocados, por isso toda acao e reversivel e o aluno retoma de onde parou.
 *
 * Toda transicao e atomica: o UPDATE so acontece se a situacao ainda e uma das
 * origens validas, entao uma aba desatualizada nunca sobrescreve o estado.
 */
class CicloDoCursoService
{
    public function pausar(Treinamento $curso, User $por): void
    {
        $this->mudar($curso, SituacaoCurso::PAUSADO, [SituacaoCurso::ATIVO], $por, 'curso pausado', 'Somente um curso ativo pode ser pausado.');
    }

    public function retomar(Treinamento $curso, User $por): void
    {
        $this->mudar($curso, SituacaoCurso::ATIVO, [SituacaoCurso::PAUSADO], $por, 'curso retomado', 'Somente um curso pausado pode ser retomado.');
    }

    /** Desativa um curso ativo ou pausado. */
    public function desativar(Treinamento $curso, User $por): void
    {
        $this->mudar($curso, SituacaoCurso::DESATIVADO, [SituacaoCurso::ATIVO, SituacaoCurso::PAUSADO], $por, 'curso desativado', 'O curso ja esta desativado.');
    }

    public function reativar(Treinamento $curso, User $por): void
    {
        $this->mudar($curso, SituacaoCurso::ATIVO, [SituacaoCurso::DESATIVADO], $por, 'curso reativado', 'Somente um curso desativado pode ser reativado.');
    }

    /**
     * @param  list<SituacaoCurso>  $origens  situacoes das quais a transicao e valida
     */
    private function mudar(Treinamento $curso, SituacaoCurso $nova, array $origens, User $por, string $detalhe, string $recusa): void
    {
        // Update direto: a situacao nao passa pelo $fillable e a trilha de acoes
        // do model nao deve disparar um "editado" alem do aviso explicito abaixo.
        // O WHERE na situacao de origem e o que torna a transicao atomica.
        $afetadas = Treinamento::query()
            ->whereKey($curso->id)
            ->whereIn('situacao', array_map(fn (SituacaoCurso $s) => $s->value, $origens))
            ->update([
                'situacao' => $nova->value,
                'situacao_alterada_em' => now(),
                'situacao_alterada_por' => $por->getKey(),
            ]);
        $curso->refresh();

        if ($afetadas !== 1) {
            throw new \DomainException($recusa.' Situacao atual: '.mb_strtolower($curso->situacaoEfetiva()->getLabel()).'. Atualize a pagina.');
        }

        // O update por query nao dispara evento de model: as telas abertas so
        // ficam sabendo da nova situacao por este aviso.
        AvisoDeCursos::avisar();

        // O video autoriza por sessao com cache: o curso que saiu do ar perde o
        // video agora. Feito logo apos o UPDATE, antes de qualquer passo que
        // possa falhar.
        if (! $nova->permiteAcessoDoAluno()) {
            app(AutorizacaoVideoService::class)->revogarAutorizacoesDoCurso($curso->id);
        }

        // Quem cumpriu a trilha durante a pausa concluiu "no papel": ao voltar a
        // ATIVO a conclusao e o certificado sao reavaliados (job idempotente).
        if ($nova === SituacaoCurso::ATIVO) {
            ReavaliarConclusoesDoCursoJob::dispatch($curso->id)->afterCommit();
        }

        // A trilha de acoes e aviso: falhar aqui nao desfaz a transicao ja feita.
        try {
            app(RegistroDeAcao::class)->registrarNoProtocolo(Treinamento::class, $curso->id, AcaoTrilha::Relacionado, $detalhe);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
