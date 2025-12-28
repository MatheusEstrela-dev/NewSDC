<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Parentesco (Membro Família)
 */
enum Parentesco: string
{
    case CONJUGE = 'CONJUGE';
    case FILHO = 'FILHO';
    case PAI = 'PAI';
    case MAE = 'MAE';
    case IRMAO = 'IRMAO';
    case AVO = 'AVO';
    case NETO = 'NETO';
    case OUTRO = 'OUTRO';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CONJUGE => 'Cônjuge',
            self::FILHO => 'Filho(a)',
            self::PAI => 'Pai',
            self::MAE => 'Mãe',
            self::IRMAO => 'Irmão(ã)',
            self::AVO => 'Avô/Avó',
            self::NETO => 'Neto(a)',
            self::OUTRO => 'Outro',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::CONJUGE => 'purple',
            self::FILHO => 'blue',
            self::PAI, self::MAE => 'green',
            self::IRMAO => 'yellow',
            self::AVO => 'orange',
            self::NETO => 'pink',
            self::OUTRO => 'gray',
        };
    }

    /**
     * Lista todos os tipos de parentesco disponíveis para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $parentesco) => [
                'value' => $parentesco->value,
                'label' => $parentesco->getLabel(),
                'color' => $parentesco->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
