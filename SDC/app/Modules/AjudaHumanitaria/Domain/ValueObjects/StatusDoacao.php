<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Status da Doação
 */
enum StatusDoacao: string
{
    case PENDENTE = 'PENDENTE';
    case RECEBIDA = 'RECEBIDA';
    case PROCESSADA = 'PROCESSADA';
    case CANCELADA = 'CANCELADA';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDENTE => 'Pendente',
            self::RECEBIDA => 'Recebida',
            self::PROCESSADA => 'Processada',
            self::CANCELADA => 'Cancelada',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::PENDENTE => 'yellow',
            self::RECEBIDA => 'blue',
            self::PROCESSADA => 'green',
            self::CANCELADA => 'red',
        };
    }

    /**
     * Verifica se pode ser processada
     */
    public function podeSerProcessada(): bool
    {
        return in_array($this, [
            self::PENDENTE,
            self::RECEBIDA,
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
