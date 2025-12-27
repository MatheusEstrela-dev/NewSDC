<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\ValueObjects;

/**
 * Value Object: Tipo de Decreto
 */
enum TipoDecreto: string
{
    case SE = 'SE';   // Situação de Emergência
    case ECP = 'ECP'; // Estado de Calamidade Pública

    /**
     * Retorna descrição completa
     */
    public function getDescricao(): string
    {
        return match($this) {
            self::SE => 'Situação de Emergência',
            self::ECP => 'Estado de Calamidade Pública',
        };
    }

    /**
     * Retorna prazo padrão de vigência em dias
     */
    public function getPrazoPadraoVigencia(): int
    {
        return match($this) {
            self::SE => 90,   // 90 dias para SE
            self::ECP => 180, // 180 dias para ECP
        };
    }

    /**
     * Lista para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $tipo) => [
                'value' => $tipo->value,
                'label' => $tipo->value,
                'descricao' => $tipo->getDescricao(),
                'prazo_padrao' => $tipo->getPrazoPadraoVigencia(),
            ],
            self::cases()
        );
    }
}
