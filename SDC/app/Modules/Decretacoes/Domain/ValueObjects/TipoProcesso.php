<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\ValueObjects;

/**
 * Value Object: Tipo de Processo (Origem)
 */
enum TipoProcesso: string
{
    case MUNICIPAL = 'MUNICIPAL';
    case ESTADUAL = 'ESTADUAL';

    /**
     * Retorna label formatado
     */
    public function getLabel(): string
    {
        return match($this) {
            self::MUNICIPAL => 'Municipal',
            self::ESTADUAL => 'Estadual',
        };
    }

    /**
     * Verifica se permite múltiplos municípios
     */
    public function permiteMultiplosMunicipios(): bool
    {
        return $this === self::ESTADUAL;
    }

    /**
     * Lista para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $tipo) => [
                'value' => $tipo->value,
                'label' => $tipo->getLabel(),
            ],
            self::cases()
        );
    }
}
