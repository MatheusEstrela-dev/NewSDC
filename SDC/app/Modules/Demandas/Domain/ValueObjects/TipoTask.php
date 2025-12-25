<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\ValueObjects;

/**
 * Value Object: Tipo de Task
 *
 * Representa os tipos de tarefas conforme papiro (Incidente, Solicitação, Mudança)
 * Baseado em ITIL/ITSM best practices
 */
enum TipoTask: string
{
    case INCIDENTE = 'incidente';
    case SOLICITACAO = 'solicitacao';
    case MUDANCA = 'mudanca';
    case PROBLEMA = 'problema';

    /**
     * Retorna o label humanizado
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::INCIDENTE => 'Incidente',
            self::SOLICITACAO => 'Solicitação de Serviço',
            self::MUDANCA => 'Mudança',
            self::PROBLEMA => 'Problema',
        };
    }

    /**
     * Alias para getLabel() - para consistência com outros enums
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * Retorna descrição do tipo
     */
    public function getDescricao(): string
    {
        return match ($this) {
            self::INCIDENTE => 'Interrupção não planejada ou redução na qualidade de um serviço',
            self::SOLICITACAO => 'Pedido de um usuário para algo que não é quebra de serviço',
            self::MUDANCA => 'Adição, modificação ou remoção de algo que pode afetar serviços',
            self::PROBLEMA => 'Causa raiz de um ou mais incidentes',
        };
    }

    /**
     * Retorna a classe CSS de cor
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::INCIDENTE => 'bg-red-100 text-red-800',
            self::SOLICITACAO => 'bg-blue-100 text-blue-800',
            self::MUDANCA => 'bg-purple-100 text-purple-800',
            self::PROBLEMA => 'bg-orange-100 text-orange-800',
        };
    }

    /**
     * Retorna ícone representativo
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::INCIDENTE => 'alert-circle',
            self::SOLICITACAO => 'inbox',
            self::MUDANCA => 'git-branch',
            self::PROBLEMA => 'search',
        };
    }

    /**
     * Retorna o prefixo para geração de protocolo
     */
    public function getProtocoloPrefix(): string
    {
        return match ($this) {
            self::INCIDENTE => 'INC',
            self::SOLICITACAO => 'REQ',
            self::MUDANCA => 'CHG',
            self::PROBLEMA => 'PRB',
        };
    }

    /**
     * Verifica se o tipo requer aprovação (Change Management)
     */
    public function requiresApproval(): bool
    {
        return match ($this) {
            self::MUDANCA => true,
            default => false,
        };
    }

    /**
     * Verifica se o tipo requer análise de impacto
     */
    public function requiresImpactAnalysis(): bool
    {
        return match ($this) {
            self::MUDANCA, self::PROBLEMA => true,
            default => false,
        };
    }

    /**
     * Retorna SLA padrão em horas para primeira resposta
     */
    public function getSlaFirstResponse(): int
    {
        return match ($this) {
            self::INCIDENTE => 1,        // 1 hora
            self::SOLICITACAO => 4,      // 4 horas
            self::MUDANCA => 8,          // 8 horas
            self::PROBLEMA => 24,        // 24 horas
        };
    }

    /**
     * Retorna todos os valores como array associativo
     *
     * @return array<string, string>
     */
    public static function toSelectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tipo) => [
                $tipo->value => $tipo->getLabel(),
            ])
            ->toArray();
    }
}
