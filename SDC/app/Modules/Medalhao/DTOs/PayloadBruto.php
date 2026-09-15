<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\DTOs;

final readonly class PayloadBruto
{
    /**
     * @param string $conteudo Payload exatamente como recebido da fonte.
     * @param string $formato Rotulo do formato, ex.: 'fdsn-text', 'obsis-csv'.
     * @param array<string, mixed> $meta Metadados da coleta: url, params, status, duracao_ms.
     */
    public function __construct(
        public string $conteudo,
        public string $formato,
        public array $meta = [],
    ) {
    }

    public function hash(): string
    {
        return hash('sha256', $this->conteudo);
    }
}
