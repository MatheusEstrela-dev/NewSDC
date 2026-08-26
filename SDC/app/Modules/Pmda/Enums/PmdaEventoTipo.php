<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Enums;

/**
 * Marcos do ciclo de vida registrados em pmda_plano_eventos.
 *
 * Cada caso carrega o texto que a serie historica mostra, para o titulo e a
 * descricao do card nascerem de UMA fonte e nao de ifs espalhados pelo controller.
 */
enum PmdaEventoTipo: string
{
    case CRIACAO      = 'CRIACAO';
    case ENVIO        = 'ENVIO';
    case DEVOLUCAO    = 'DEVOLUCAO';
    case APROVACAO    = 'APROVACAO';
    case ARQUIVAMENTO = 'ARQUIVAMENTO';

    public function titulo(): string
    {
        return match ($this) {
            self::CRIACAO      => 'Protocolo Criado',
            self::ENVIO        => 'Enviado para Análise',
            self::DEVOLUCAO    => 'Devolvido para Alteração',
            self::APROVACAO    => 'PMDA Aprovado',
            self::ARQUIVAMENTO => 'PMDA Arquivado',
        };
    }

    public function descricao(?string $motivo = null): string
    {
        $sufixo = ($motivo !== null && trim($motivo) !== '') ? ' Motivo: '.trim($motivo) : '';

        return match ($this) {
            self::CRIACAO      => 'PMDA criado no sistema SDC.',
            self::ENVIO        => 'PMDA encaminhado para análise da CEDEC-MG.',
            self::DEVOLUCAO    => 'CEDEC-MG devolveu o PMDA ao município para ajustes.'.$sufixo,
            self::APROVACAO    => 'Plano aprovado pela CEDEC-MG.'.$sufixo,
            self::ARQUIVAMENTO => 'Plano arquivado pela CEDEC-MG.'.$sufixo,
        };
    }

    /**
     * Categoria que o modal usa para separar as abas: 'criacao' fica so na
     * timeline, 'analise' e 'notificacao' entram tambem na aba Análises.
     */
    public function categoria(): string
    {
        return match ($this) {
            self::CRIACAO                     => 'criacao',
            self::ENVIO, self::APROVACAO      => 'analise',
            self::DEVOLUCAO, self::ARQUIVAMENTO => 'notificacao',
        };
    }

    /** Eventos de tramite/decisao — os que aparecem na aba Análises. */
    public function eAnalise(): bool
    {
        return $this !== self::CRIACAO;
    }
}
