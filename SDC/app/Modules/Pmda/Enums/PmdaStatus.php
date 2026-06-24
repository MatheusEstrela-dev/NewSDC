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
        return ! in_array($this, [self::RASCUNHO, self::COMPLETO, self::EM_ANALISE, self::APROVADO], true);
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

    public function getColorClass(): string
    {
        return match ($this) {
            self::RASCUNHO   => 'bg-slate-100 text-slate-800',
            self::COMPLETO   => 'bg-blue-100 text-blue-800',
            self::EM_ANALISE => 'bg-indigo-100 text-indigo-800',
            self::APROVADO   => 'bg-green-100 text-green-800',
            self::ATENDIDO   => 'bg-emerald-100 text-emerald-800',
            self::ARQUIVADO  => 'bg-yellow-100 text-yellow-800',
            self::ANULADO, self::CANCELADO => 'bg-red-100 text-red-800',
            self::ENCERRADO  => 'bg-gray-200 text-gray-700',
        };
    }
}
