<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

/** Mensagem aceita pela caixa; repetida = mesma Idempotency-Key e mesmo corpo. */
final class ResultadoDoRecebimento
{
    public function __construct(
        public readonly string $id,
        public readonly string $traceId,
        public readonly bool $repetida,
    ) {
    }
}
