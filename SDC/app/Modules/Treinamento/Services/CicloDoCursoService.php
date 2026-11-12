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

/**
 * Ciclo de vida do curso EAD: pausar/retomar (suspensao temporaria) e
 * desativar/reativar (encerramento reversivel). So muda a situacao, quem e
 * quando: publicacao, inscricoes, progresso, entregas e certificados nao sao
 * tocados, por isso toda acao e reversivel e o aluno retoma de onde parou.
 */
class CicloDoCursoService
{
    public function pausar(Treinamento $curso, User $por): void
    {
        $this->exigirSituacao($curso, SituacaoCurso::ATIVO, 'Somente um curso ativo pode ser pausado.');
        $this->mudar($curso, SituacaoCurso::PAUSADO, $por, 'curso pausado');
    }

    public function retomar(Treinamento $curso, User $por): void
    {
        $this->exigirSituacao($curso, SituacaoCurso::PAUSADO, 'Somente um curso pausado pode ser retomado.');
        $this->mudar($curso, SituacaoCurso::ATIVO, $por, 'curso retomado');
    }

    /** Desativa um curso ativo ou pausado. */
    public function desativar(Treinamento $curso, User $por): void
    {
        if ($curso->situacaoEfetiva() === SituacaoCurso::DESATIVADO) {
            throw new \DomainException('O curso ja esta desativado.');
        }

        $this->mudar($curso, SituacaoCurso::DESATIVADO, $por, 'curso desativado');
    }

    public function reativar(Treinamento $curso, User $por): void
    {
        $this->exigirSituacao($curso, SituacaoCurso::DESATIVADO, 'Somente um curso desativado pode ser reativado.');
        $this->mudar($curso, SituacaoCurso::ATIVO, $por, 'curso reativado');
    }

    private function exigirSituacao(Treinamento $curso, SituacaoCurso $esperada, string $mensagem): void
    {
        if ($curso->situacaoEfetiva() !== $esperada) {
            throw new \DomainException($mensagem);
        }
    }

    private function mudar(Treinamento $curso, SituacaoCurso $nova, User $por, string $detalhe): void
    {
        // Update direto: a situacao nao passa pelo $fillable e a trilha de acoes
        // do model nao deve disparar um "editado" alem do aviso explicito abaixo.
        Treinamento::query()->whereKey($curso->id)->update([
            'situacao' => $nova->value,
            'situacao_alterada_em' => now(),
            'situacao_alterada_por' => $por->getKey(),
        ]);
        $curso->refresh();

        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $curso->id,
            AcaoTrilha::Relacionado,
            $detalhe,
        );

        // O video autoriza por sessao com cache: o curso que saiu do ar perde o video agora.
        if (! $nova->permiteAcessoDoAluno()) {
            app(AutorizacaoVideoService::class)->revogarAutorizacoesEmCache();
        }

        // Quem cumpriu a trilha durante a pausa concluiu "no papel": ao voltar a
        // ATIVO a conclusao e o certificado sao reavaliados (job idempotente).
        if ($nova === SituacaoCurso::ATIVO) {
            ReavaliarConclusoesDoCursoJob::dispatch($curso->id)->afterCommit();
        }
    }
}
