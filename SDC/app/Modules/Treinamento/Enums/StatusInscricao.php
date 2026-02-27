<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum StatusInscricao: string
{
    case PENDENTE = 'PENDENTE';
    case APROVADA = 'APROVADA';
    case REPROVADA = 'REPROVADA';
    case CANCELADA = 'CANCELADA';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::APROVADA => 'Aprovada',
            self::REPROVADA => 'Reprovada',
            self::CANCELADA => 'Cancelada',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PENDENTE => 'yellow',
            self::APROVADA => 'green',
            self::REPROVADA => 'red',
            self::CANCELADA => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::APROVADA ||
            $this === self::REPROVADA ||
            $this === self::CANCELADA;
    }

    public function podeRegistrarFrequencia(): bool
    {
        return $this === self::APROVADA;
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
