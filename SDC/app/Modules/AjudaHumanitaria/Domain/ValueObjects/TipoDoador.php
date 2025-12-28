<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Tipo de Doador
 */
enum TipoDoador: string
{
    case PESSOA_FISICA = 'PESSOA_FISICA';
    case PESSOA_JURIDICA = 'PESSOA_JURIDICA';
    case ANONIMO = 'ANONIMO';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PESSOA_FISICA => 'Pessoa Física',
            self::PESSOA_JURIDICA => 'Pessoa Jurídica',
            self::ANONIMO => 'Anônimo',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::PESSOA_FISICA => 'blue',
            self::PESSOA_JURIDICA => 'purple',
            self::ANONIMO => 'gray',
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
