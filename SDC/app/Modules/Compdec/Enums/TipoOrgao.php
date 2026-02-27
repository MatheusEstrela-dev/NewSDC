<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Enums;

enum TipoOrgao: string
{
    case COMPDEC = 'compdec';
    case REDEC = 'redec';
    case CEDEC = 'cedec';

    public function getLabel(): string
    {
        return match($this) {
            self::COMPDEC => 'COMPDEC',
            self::REDEC => 'REDEC',
            self::CEDEC => 'CEDEC',
        };
    }

    public function getSigla(): string
    {
        return $this->getLabel();
    }

    public function getBadgeColor(): string
    {
        return match($this) {
            self::COMPDEC => 'blue',
            self::REDEC => 'yellow',
            self::CEDEC => 'purple',
        };
    }

    public function podeSerSubordinadoDe(TipoOrgao $superior): bool
    {
        return match($this) {
            self::COMPDEC => $superior === self::REDEC,
            self::REDEC => $superior === self::CEDEC,
            self::CEDEC => false,
        };
    }

    public function getTipoSuperiorValido(): ?TipoOrgao
    {
        return match($this) {
            self::COMPDEC => self::REDEC,
            self::REDEC => self::CEDEC,
            self::CEDEC => null,
        };
    }

    public function getNivelHierarquico(): int
    {
        return match($this) {
            self::CEDEC => 1,
            self::REDEC => 2,
            self::COMPDEC => 3,
        };
    }

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
