<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

use App\Modules\Plantao\Models\ViaturaReserva;

class ReservaListDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $viatura_id,
        public readonly string $viatura_prefixo,
        public readonly string $viatura_placa,
        public readonly ?string $viatura_modelo,
        public readonly int $agente_id,
        public readonly string $agente_nome,
        public readonly string $inicio_previsto,
        public readonly string $fim_previsto,
        public readonly string $status,
        public readonly string $status_valor,
        public readonly ?string $destino,
        public readonly ?string $motivo,
        public readonly ?int $movimentacao_id,
        public readonly ?string $checkin_em,
        public readonly ?string $checkout_em,
        public readonly ?string $cancelada_por_nome,
        public readonly ?string $cancelamento_motivo,
        public readonly bool $pode_cancelar,
    ) {
    }

    public static function fromModel(ViaturaReserva $reserva): self
    {
        return new self(
            id: $reserva->id,
            viatura_id: $reserva->viatura_id,
            viatura_prefixo: $reserva->viatura?->prefixo ?? '',
            viatura_placa: $reserva->viatura?->placa ?? '',
            viatura_modelo: $reserva->viatura?->modelo,
            agente_id: $reserva->agente_id,
            agente_nome: $reserva->agente_nome,
            // ISO-8601: a tela formata no fuso do navegador. Mandar ja
            // formatado obrigaria o front a reinterpretar string em pt-BR.
            inicio_previsto: $reserva->inicio_previsto->toIso8601String(),
            fim_previsto: $reserva->fim_previsto->toIso8601String(),
            status: $reserva->status->label(),
            status_valor: $reserva->status->value,
            destino: $reserva->destino,
            motivo: $reserva->motivo,
            movimentacao_id: $reserva->movimentacao_id,
            checkin_em: $reserva->checkin_em?->toIso8601String(),
            checkout_em: $reserva->checkout_em?->toIso8601String(),
            cancelada_por_nome: $reserva->cancelada_por_nome,
            cancelamento_motivo: $reserva->cancelamento_motivo,
            pode_cancelar: $reserva->status->podeCancelar(),
        );
    }

    public static function collection(iterable $items): array
    {
        return array_map(
            fn(ViaturaReserva $item) => self::fromModel($item),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
