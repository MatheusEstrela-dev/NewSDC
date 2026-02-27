<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Enums;

enum StatusOrgao: string
{
    case ATIVO = 'ativo';
    case INATIVO = 'inativo';
    case EM_IMPLANTACAO = 'em_implantacao';
    case SUSPENSO = 'suspenso';

    public function getLabel(): string
    {
        return match($this) {
            self::ATIVO => 'Ativo',
            self::INATIVO => 'Inativo',
            self::EM_IMPLANTACAO => 'Em Implantacao',
            self::SUSPENSO => 'Suspenso',
        };
    }

    public function getBadgeColor(): string
    {
        return match($this) {
            self::ATIVO => 'green',
            self::INATIVO => 'gray',
            self::EM_IMPLANTACAO => 'blue',
            self::SUSPENSO => 'yellow',
        };
    }

    public function podeReceberUsuarios(): bool
    {
        return match($this) {
            self::ATIVO, self::EM_IMPLANTACAO => true,
            self::INATIVO, self::SUSPENSO => false,
        };
    }

    public function isOperacional(): bool
    {
        return $this === self::ATIVO;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->getLabel(),
            ],
            self::cases()
        );
    }
}
