<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Tipos do historico do curso (treinamento.curso_eventos.tipo).
 *
 * A coluna NAO tem CHECK: este enum e a fonte da verdade. Tipo novo entra aqui
 * sem migration; tipo lido do banco que nao exista mais aqui volta como null
 * em CursoEvento::tipoEnum() e a descricao gravada na linha segue legivel.
 */
enum TipoEventoCurso: string
{
    case CURSO_CRIADO = 'CURSO_CRIADO';
    case CURSO_ATUALIZADO = 'CURSO_ATUALIZADO';
    case CURSO_PUBLICADO = 'CURSO_PUBLICADO';
    case STATUS_ALTERADO = 'STATUS_ALTERADO';
    case CURSO_CANCELADO = 'CURSO_CANCELADO';
    case CURSO_PAUSADO = 'CURSO_PAUSADO';
    case CURSO_RETOMADO = 'CURSO_RETOMADO';
    case CURSO_DESATIVADO = 'CURSO_DESATIVADO';
    case CURSO_REATIVADO = 'CURSO_REATIVADO';
    case VAGAS_ALTERADAS = 'VAGAS_ALTERADAS';
    case PERIODO_ALTERADO = 'PERIODO_ALTERADO';
    case CRONOGRAMA_ADICIONADO = 'CRONOGRAMA_ADICIONADO';
    case CRONOGRAMA_ATUALIZADO = 'CRONOGRAMA_ATUALIZADO';
    case CRONOGRAMA_REMOVIDO = 'CRONOGRAMA_REMOVIDO';
    case VERSAO_CRIADA = 'VERSAO_CRIADA';
    case VERSAO_PUBLICADA = 'VERSAO_PUBLICADA';

    public function getLabel(): string
    {
        return match ($this) {
            self::CURSO_CRIADO => 'Curso criado',
            self::CURSO_ATUALIZADO => 'Curso atualizado',
            self::CURSO_PUBLICADO => 'Curso publicado',
            self::STATUS_ALTERADO => 'Status alterado',
            self::CURSO_CANCELADO => 'Curso cancelado',
            self::CURSO_PAUSADO => 'Curso pausado',
            self::CURSO_RETOMADO => 'Curso retomado',
            self::CURSO_DESATIVADO => 'Curso desativado',
            self::CURSO_REATIVADO => 'Curso reativado',
            self::VAGAS_ALTERADAS => 'Vagas alteradas',
            self::PERIODO_ALTERADO => 'Periodo alterado',
            self::CRONOGRAMA_ADICIONADO => 'Item do cronograma adicionado',
            self::CRONOGRAMA_ATUALIZADO => 'Item do cronograma atualizado',
            self::CRONOGRAMA_REMOVIDO => 'Item do cronograma removido',
            self::VERSAO_CRIADA => 'Versao criada',
            self::VERSAO_PUBLICADA => 'Versao publicada',
        };
    }

    /** Variante do atomo Badge na linha do historico. */
    public function getVariant(): string
    {
        return match ($this) {
            self::CURSO_CANCELADO, self::CURSO_DESATIVADO, self::CRONOGRAMA_REMOVIDO => 'danger',
            self::CURSO_PAUSADO => 'warning',
            self::CURSO_PUBLICADO, self::VERSAO_PUBLICADA, self::CURSO_REATIVADO, self::CURSO_RETOMADO, self::CRONOGRAMA_ADICIONADO => 'success',
            default => 'info',
        };
    }
}
