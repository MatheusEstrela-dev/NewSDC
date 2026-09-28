<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Enums;

/**
 * Projecao do status ITIL para a tela.
 *
 * O dominio segue com os sete status; quem usa o sistema ve o fluxo curto que a
 * equipe ja conhecia no cedec-demanda. Nao existe coluna para isto: a etapa e
 * sempre derivada do status, entao as duas nunca divergem.
 */
enum EtapaDemanda: string
{
    case ABERTO = 'aberto';
    case EM_ANDAMENTO = 'em_andamento';
    case CONCLUIDO = 'concluido';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::ABERTO => 'Aberto',
            self::EM_ANDAMENTO => 'Em andamento',
            self::CONCLUIDO => 'Concluído',
            self::CANCELADO => 'Cancelado',
        };
    }

    /** @return list<StatusDemanda> */
    public function status(): array
    {
        return array_values(array_filter(
            StatusDemanda::cases(),
            fn (StatusDemanda $status): bool => $status->etapa() === $this,
        ));
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $etapa): array => ['value' => $etapa->value, 'label' => $etapa->label()],
            self::cases(),
        );
    }
}
