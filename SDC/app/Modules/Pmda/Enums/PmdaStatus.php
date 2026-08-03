<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Enums;

enum PmdaStatus: string
{
    case RASCUNHO   = 'RASCUNHO';
    case COMPLETO   = 'COMPLETO';
    case EM_ANALISE = 'EM_ANALISE';
    case APROVADO   = 'APROVADO';
    case ATENDIDO   = 'ATENDIDO';
    case ARQUIVADO  = 'ARQUIVADO';
    case ANULADO    = 'ANULADO';
    case CANCELADO  = 'CANCELADO';
    case ENCERRADO  = 'ENCERRADO';

    /** Transicoes permitidas (origem => destinos). */
    public function transicoes(): array
    {
        return match ($this) {
            self::RASCUNHO   => [self::COMPLETO, self::ANULADO, self::CANCELADO],
            self::COMPLETO   => [self::RASCUNHO, self::EM_ANALISE, self::ANULADO, self::CANCELADO],
            self::EM_ANALISE => [self::APROVADO, self::RASCUNHO, self::ARQUIVADO, self::ANULADO, self::CANCELADO],
            self::APROVADO   => [self::ATENDIDO, self::CANCELADO],
            default          => [], // ATENDIDO, ARQUIVADO, ANULADO, CANCELADO, ENCERRADO sao terminais
        };
    }

    public function podeTransicionarPara(PmdaStatus $destino): bool
    {
        return in_array($destino, $this->transicoes(), true);
    }

    public function permiteCopia(): bool
    {
        // Duplicacao disponivel em qualquer status: gera sempre um novo PMDA em
        // RASCUNHO com os mesmos dados (fallback identico) e proximo protocolo
        // sequencial. A regra de data minima de copia segue no PmdaCopiaService.
        return true;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::RASCUNHO   => 'Em Edição',
            self::COMPLETO   => 'Completo',
            self::EM_ANALISE => 'Em Análise',
            self::APROVADO   => 'Aprovado',
            self::ATENDIDO   => 'Atendido',
            self::ARQUIVADO  => 'Arquivado',
            self::ANULADO    => 'Anulado',
            self::CANCELADO  => 'Cancelado',
            self::ENCERRADO  => 'Encerrado',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * Cor do status na paleta do componente Badge.
     *
     * Devolve NOME de cor, e nao classe Tailwind: classe de CSS e decisao de
     * apresentacao e nao pertence ao dominio. Com o nome, o Badge aplica a receita
     * de pill do sistema (bg-100 / text-700 / border-300 + par dark) num lugar so,
     * e mudar o padrao visual deixa de exigir alterar enum de backend.
     */
    public function getCor(): string
    {
        return match ($this) {
            self::RASCUNHO   => 'amber',    // Em Edicao
            self::COMPLETO   => 'indigo',
            self::EM_ANALISE => 'blue',
            self::APROVADO   => 'green',
            self::ATENDIDO   => 'emerald',
            self::ARQUIVADO  => 'red',
            self::ANULADO, self::CANCELADO => 'red',
            // Encerrado sem desfecho: cinza mais forte que o neutro comum.
            self::ENCERRADO  => 'slate-forte',
        };
    }

    /**
     * @deprecated Use getCor(). Mantido apenas para nao quebrar consumidor externo
     *             que ainda leia status_color; sai quando ninguem mais depender.
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::RASCUNHO   => 'bg-amber-100 text-amber-800',
            self::COMPLETO   => 'bg-indigo-100 text-indigo-800',
            self::EM_ANALISE => 'bg-blue-100 text-blue-800',
            self::APROVADO   => 'bg-green-100 text-green-800',
            self::ATENDIDO   => 'bg-emerald-100 text-emerald-800',
            self::ARQUIVADO  => 'bg-red-100 text-red-800',
            self::ANULADO, self::CANCELADO => 'bg-red-100 text-red-800',
            self::ENCERRADO  => 'bg-gray-200 text-gray-700',
        };
    }
}
