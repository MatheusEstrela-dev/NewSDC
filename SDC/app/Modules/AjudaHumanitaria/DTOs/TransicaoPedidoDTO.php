<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Pedido de mudanca de status (RN-12). A observacao vai para o log de
 * tramitacao (RN-14).
 */
final readonly class TransicaoPedidoDTO
{
    public function __construct(
        public StatusPedidoAh $statusAlvo,
        public ?string $observacao = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $observacao = $data['observacao'] ?? null;
        $observacao = $observacao === null ? null : trim((string) $observacao);

        return new self(
            statusAlvo: StatusPedidoAh::from((int) ($data['status_alvo'] ?? -1)),
            observacao: $observacao === '' ? null : $observacao,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status_alvo' => $this->statusAlvo->value,
            'observacao'  => $this->observacao,
        ];
    }
}
