<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum StatusCertificado: string
{
    case PENDENTE = 'PENDENTE';
    case GERADO = 'GERADO';
    case CANCELADO = 'CANCELADO';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::GERADO => 'Disponivel',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PENDENTE => 'yellow',
            self::GERADO => 'green',
            self::CANCELADO => 'red',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->getLabel(),
                'color' => $status->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
