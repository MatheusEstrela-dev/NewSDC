<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Enums;

enum NivelPrecipitacao: string
{
    case SEM_CHUVA = 'sem_chuva';
    case BAIXO = 'baixo';
    case MODERADO = 'moderado';
    case FORTE = 'forte';
    case MUITO_FORTE = 'muito_forte';
    case EXTREMO = 'extremo';

    public static function fromMilimetros(float $mm): self
    {
        return match (true) {
            $mm == 0 => self::SEM_CHUVA,
            $mm > 0 && $mm <= 10 => self::BAIXO,
            $mm > 10 && $mm <= 30 => self::MODERADO,
            $mm > 30 && $mm <= 60 => self::FORTE,
            $mm > 60 && $mm <= 100 => self::MUITO_FORTE,
            default => self::EXTREMO,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SEM_CHUVA => 'Sem Chuva',
            self::BAIXO => 'Baixo',
            self::MODERADO => 'Moderado',
            self::FORTE => 'Forte',
            self::MUITO_FORTE => 'Muito Forte',
            self::EXTREMO => 'Extremo',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SEM_CHUVA => 'green',
            self::BAIXO => 'blue',
            self::MODERADO => 'yellow',
            self::FORTE => 'orange',
            self::MUITO_FORTE => 'red',
            self::EXTREMO => 'darkred',
        };
    }

    public function getBadgeColor(): string
    {
        return $this->getColor();
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $nivel) => [
                'value' => $nivel->value,
                'label' => $nivel->getLabel(),
                'color' => $nivel->getColor(),
            ],
            self::cases()
        );
    }
}
