<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum StatusTreinamento: string
{
    case PLANEJADO = 'PLANEJADO';
    case EM_ANDAMENTO = 'EM_ANDAMENTO';
    case CONCLUIDO = 'CONCLUIDO';
    case CANCELADO = 'CANCELADO';

    public function getLabel(): string
    {
        return match ($this) {
            self::PLANEJADO => 'Planejado',
            self::EM_ANDAMENTO => 'Em Andamento',
            self::CONCLUIDO => 'Concluido',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PLANEJADO => 'yellow',
            self::EM_ANDAMENTO => 'blue',
            self::CONCLUIDO => 'green',
            self::CANCELADO => 'red',
        };
    }

    public function podeReceberInscricoes(): bool
    {
        return $this === self::PLANEJADO || $this === self::EM_ANDAMENTO;
    }

    public function podeRegistrarFrequencia(): bool
    {
        return $this === self::EM_ANDAMENTO;
    }

    public function canTransitionTo(StatusTreinamento $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedTransitions(), true);
    }

    public function getAllowedTransitions(): array
    {
        return match ($this) {
            self::PLANEJADO => [self::EM_ANDAMENTO, self::CANCELADO],
            self::EM_ANDAMENTO => [self::CONCLUIDO, self::CANCELADO],
            self::CONCLUIDO => [],
            self::CANCELADO => [],
        };
    }

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
