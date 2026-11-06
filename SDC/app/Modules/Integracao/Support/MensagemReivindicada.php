<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

/** Mensagem travada por um worker (status processando), com o payload decifrado. */
final class MensagemReivindicada
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $parametrosRota
     */
    public function __construct(
        public readonly string $id,
        public readonly string $operacao,
        public readonly array $payload,
        public readonly array $parametrosRota,
        public readonly string $metodo,
        public readonly string $caminho,
        public readonly ?int $userId,
        public readonly ?int $tokenId,
        public readonly int $tentativas,
        public readonly ?string $recursoChave,
        public readonly ?string $traceId,
    ) {
    }

    /** @param array<string, mixed> $payloadValidado */
    public function paraAplicador(array $payloadValidado): MensagemDeEntrada
    {
        return new MensagemDeEntrada(
            id: $this->id,
            operacao: $this->operacao,
            payload: $payloadValidado,
            parametrosRota: $this->parametrosRota,
            userId: $this->userId,
            tokenId: $this->tokenId,
        );
    }
}
