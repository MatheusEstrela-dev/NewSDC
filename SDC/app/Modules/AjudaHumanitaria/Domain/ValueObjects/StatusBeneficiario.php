<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Status do Beneficiário
 */
enum StatusBeneficiario: string
{
    case ATIVO = 'ATIVO';
    case INATIVO = 'INATIVO';
    case FALECIDO = 'FALECIDO';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ATIVO => 'Ativo',
            self::INATIVO => 'Inativo',
            self::FALECIDO => 'Falecido',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::ATIVO => 'green',
            self::INATIVO => 'yellow',
            self::FALECIDO => 'red',
        };
    }

    /**
     * Verifica se beneficiário pode receber auxílio
     */
    public function podeReceberAuxilio(): bool
    {
        return $this === self::ATIVO;
    }

    /**
     * Lista todos os status disponíveis para select
     */
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
