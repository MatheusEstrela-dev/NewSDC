<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Forma de Recebimento da Doação
 */
enum FormaRecebimento: string
{
    case DINHEIRO = 'DINHEIRO';
    case PIX = 'PIX';
    case TED = 'TED';
    case CHEQUE = 'CHEQUE';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DINHEIRO => 'Dinheiro',
            self::PIX => 'PIX',
            self::TED => 'TED',
            self::CHEQUE => 'Cheque',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::DINHEIRO => 'green',
            self::PIX => 'blue',
            self::TED => 'purple',
            self::CHEQUE => 'yellow',
        };
    }

    /**
     * Lista todas as formas disponíveis para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $forma) => [
                'value' => $forma->value,
                'label' => $forma->getLabel(),
                'color' => $forma->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
