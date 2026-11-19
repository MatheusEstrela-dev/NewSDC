<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Situacao da avaliacao do curso (etapa final obrigatoria) para o aluno.
 *
 * BLOQUEADA  requisitos do criterio ainda nao cumpridos (trilha incompleta,
 *            evento nao concluido ou sem frequencia): cadeado.
 * LIBERADA   requisitos cumpridos e ainda nao avaliou: falta so avaliar para
 *            concluir (ou, no legado ja concluido, avaliacao opcional).
 * ENVIADA    ja avaliou.
 */
enum SituacaoAvaliacaoCurso: string
{
    case BLOQUEADA = 'BLOQUEADA';
    case LIBERADA = 'LIBERADA';
    case ENVIADA = 'ENVIADA';

    public static function de(bool $avaliada, bool $requisitosCumpridos): self
    {
        return match (true) {
            $avaliada => self::ENVIADA,
            $requisitosCumpridos => self::LIBERADA,
            default => self::BLOQUEADA,
        };
    }
}
