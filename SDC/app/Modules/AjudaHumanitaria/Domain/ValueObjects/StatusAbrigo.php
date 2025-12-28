<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Status do Abrigo
 */
enum StatusAbrigo: string
{
    case ATIVO = 'ATIVO';
    case LOTADO = 'LOTADO';
    case DESATIVADO = 'DESATIVADO';
    case MANUTENCAO = 'MANUTENCAO';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ATIVO => 'Ativo',
            self::LOTADO => 'Lotado',
            self::DESATIVADO => 'Desativado',
            self::MANUTENCAO => 'Em Manutenção',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::ATIVO => 'green',
            self::LOTADO => 'red',
            self::DESATIVADO => 'gray',
            self::MANUTENCAO => 'yellow',
        };
    }

    /**
     * Verifica se pode receber novos beneficiários
     */
    public function podeReceberBeneficiarios(): bool
    {
        return in_array($this, [
            self::ATIVO,
        ]);
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
