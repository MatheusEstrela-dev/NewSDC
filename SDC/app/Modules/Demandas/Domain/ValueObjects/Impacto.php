<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\ValueObjects;

/**
 * Value Object: Impacto da Tarefa
 *
 * Representa o grau de impacto que a tarefa/incidente causa no negócio
 */
enum Impacto: string
{
    case ALTO = 'alto';
    case MEDIO = 'medio';
    case BAIXO = 'baixo';

    /**
     * Retorna o label humanizado
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ALTO => 'Alto',
            self::MEDIO => 'Médio',
            self::BAIXO => 'Baixo',
        };
    }

    /**
     * Alias para getLabel() - para consistência
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * Retorna descrição do impacto
     */
    public function getDescricao(): string
    {
        return match ($this) {
            self::ALTO => 'Afeta múltiplos usuários ou serviços críticos',
            self::MEDIO => 'Afeta um grupo de usuários ou serviço não crítico',
            self::BAIXO => 'Afeta usuário individual ou serviço de baixa prioridade',
        };
    }

    /**
     * Retorna a classe CSS de cor
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::ALTO => 'bg-red-100 text-red-800',
            self::MEDIO => 'bg-yellow-100 text-yellow-800',
            self::BAIXO => 'bg-blue-100 text-blue-800',
        };
    }

    /**
     * Retorna peso numérico para cálculos
     */
    public function getPeso(): int
    {
        return match ($this) {
            self::ALTO => 3,
            self::MEDIO => 2,
            self::BAIXO => 1,
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
            ->mapWithKeys(fn (self $impacto) => [
                $impacto->value => $impacto->getLabel(),
            ])
            ->toArray();
    }
}
