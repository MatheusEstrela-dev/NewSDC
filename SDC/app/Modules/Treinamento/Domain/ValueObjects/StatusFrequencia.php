<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Domain\ValueObjects;

enum StatusFrequencia: string
{
    case PRESENTE = 'PRESENTE';
    case AUSENTE = 'AUSENTE';
    case JUSTIFICADA = 'JUSTIFICADA';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENTE => 'Presente',
            self::AUSENTE => 'Ausente',
            self::JUSTIFICADA => 'Ausência Justificada',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PRESENTE => 'green',
            self::AUSENTE => 'red',
            self::JUSTIFICADA => 'yellow',
        };
    }

    public function contabilizaParaAprovacao(): bool
    {
        return $this === self::PRESENTE || $this === self::JUSTIFICADA;
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
