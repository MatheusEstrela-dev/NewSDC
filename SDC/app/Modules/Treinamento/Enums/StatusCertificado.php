<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum StatusCertificado: string
{
    case PENDENTE = 'PENDENTE';
    case GERADO = 'GERADO';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::GERADO => 'Disponivel',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PENDENTE => 'yellow',
            self::GERADO => 'green',
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
