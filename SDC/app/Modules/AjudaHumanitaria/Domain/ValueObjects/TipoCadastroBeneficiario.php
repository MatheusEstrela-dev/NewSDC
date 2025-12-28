<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Tipo de Cadastro do Beneficiário
 */
enum TipoCadastroBeneficiario: string
{
    case INDIVIDUAL = 'INDIVIDUAL';
    case FAMILIAR = 'FAMILIAR';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'Individual',
            self::FAMILIAR => 'Familiar',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'blue',
            self::FAMILIAR => 'purple',
        };
    }

    /**
     * Lista todos os tipos disponíveis para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $tipo) => [
                'value' => $tipo->value,
                'label' => $tipo->getLabel(),
                'color' => $tipo->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
