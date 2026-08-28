<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusEscalaItem: string
{
    case ESCALADO = 'ESCALADO';
    case CUMPRIDO = 'CUMPRIDO';
    case FALTOU = 'FALTOU';
    case SUBSTITUIDO = 'SUBSTITUIDO';

    public function label(): string
    {
        return match ($this) {
            self::ESCALADO => 'Escalado',
            self::CUMPRIDO => 'Cumprido',
            self::FALTOU => 'Faltou',
            self::SUBSTITUIDO => 'Substituido',
        };
    }

    /**
     * Vaga ainda a cumprir: e o unico estado que gera lembrete e que aceita
     * "Assumir turno".
     */
    public function pendente(): bool
    {
        return $this === self::ESCALADO;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
