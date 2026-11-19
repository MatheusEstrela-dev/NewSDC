<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\NotaAvaliacaoCurso;
use App\Modules\Treinamento\Models\AvaliacaoCurso;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Avaliacao do curso, a etapa final obrigatoria, igual nos dois escopos
 * (servidor e cidadao). So abre quando os requisitos do criterio estao
 * cumpridos (ConclusaoService::requisitosCumpridos); gravada a avaliacao, a
 * conclusao e o certificado saem pelo caminho de sempre
 * (CertificadoService::emitirSeElegivel -> ConclusaoService::registrar), na
 * mesma transacao: ou o aluno avaliou e concluiu, ou nada foi gravado.
 */
class AvaliacaoCursoService
{
    public function __construct(
        private readonly ConclusaoService $conclusao,
        private readonly CertificadoService $certificados,
    ) {}

    /**
     * Grava a avaliacao (uma por inscricao, imutavel), conclui o curso e
     * emite o certificado.
     *
     * @throws DomainException avaliacao ainda bloqueada ou curso ja avaliado
     */
    public function avaliar(Inscricao $inscricao, NotaAvaliacaoCurso $nota, ?string $comentario): AvaliacaoCurso
    {
        $comentario = $comentario !== null ? trim($comentario) : null;

        return ConexaoTreinamento::transacao(function () use ($inscricao, $nota, $comentario) {
            // Lock da inscricao: serializa com o registrar() (mesma linha) e com um envio duplo.
            $travada = Inscricao::query()->with('treinamento')->lockForUpdate()->findOrFail($inscricao->id);

            if (AvaliacaoCurso::query()->where('inscricao_id', $travada->id)->exists()) {
                throw new DomainException('Você já avaliou este curso.');
            }

            if (! $this->conclusao->requisitosCumpridos($travada)) {
                throw new DomainException('A avaliação é liberada ao final do curso. Conclua todas as etapas para avaliar.');
            }

            try {
                // Transacao propria = savepoint: a violacao do UNIQUE (corrida que
                // escapou do lock) nao aborta a transacao externa.
                $avaliacao = ConexaoTreinamento::transacao(fn () => AvaliacaoCurso::create([
                    'inscricao_id' => $travada->id,
                    'treinamento_id' => $travada->treinamento_id,
                    'nota' => $nota,
                    'comentario' => $comentario === '' ? null : $comentario,
                ]));
            } catch (UniqueConstraintViolationException) {
                throw new DomainException('Você já avaliou este curso.');
            }

            // Conclui (agora com a avaliacao) e emite o certificado. Idempotente:
            // no legado ja concluido so garante o certificado que ja existe.
            $this->certificados->emitirSeElegivel($inscricao);

            return $avaliacao;
        });
    }
}
