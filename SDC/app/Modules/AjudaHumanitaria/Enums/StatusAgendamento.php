<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Situacao do agendamento de retirada de material (RN-21).
 */
enum StatusAgendamento: string
{
    case Pendente = 'pendente';
    case Aprovado = 'aprovado';
    case Recusado = 'recusado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Aguardando aprovação',
            self::Aprovado => 'Aprovado',
            self::Recusado => 'Recusado',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
