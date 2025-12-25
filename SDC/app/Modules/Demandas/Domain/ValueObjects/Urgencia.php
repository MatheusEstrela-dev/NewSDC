<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\ValueObjects;

/**
 * Value Object: Urgência da Tarefa
 *
 * Representa a velocidade com que a tarefa precisa ser resolvida
 */
enum Urgencia: string
{
    case ALTA = 'alta';
    case MEDIA = 'media';
    case BAIXA = 'baixa';

    /**
     * Retorna o label humanizado
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ALTA => 'Alta',
            self::MEDIA => 'Média',
            self::BAIXA => 'Baixa',
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
     * Retorna descrição da urgência
     */
    public function getDescricao(): string
    {
        return match ($this) {
            self::ALTA => 'Requer ação imediata',
            self::MEDIA => 'Pode aguardar, mas não indefinidamente',
            self::BAIXA => 'Pode ser programada para momento oportuno',
        };
    }

    /**
     * Retorna a classe CSS de cor
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::ALTA => 'bg-red-100 text-red-800',
            self::MEDIA => 'bg-yellow-100 text-yellow-800',
            self::BAIXA => 'bg-green-100 text-green-800',
        };
    }

    /**
     * Retorna peso numérico para cálculos
     */
    public function getPeso(): int
    {
        return match ($this) {
            self::ALTA => 3,
            self::MEDIA => 2,
            self::BAIXA => 1,
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
            ->mapWithKeys(fn (self $urgencia) => [
                $urgencia->value => $urgencia->getLabel(),
            ])
            ->toArray();
    }
}
