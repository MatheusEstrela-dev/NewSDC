<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain;

/**
 * Origem de uma transicao de status, passada como argumento a cada chamada.
 *
 * Objeto de valor e nao servico com estado: os services do PAE sao singleton
 * no Octane, e um "contexto ativo" guardado no container vazaria entre requests.
 */
final class ContextoTransicao
{
    private const MANUAL = 'manual';

    private const EMISSAO_CCPAE = 'emissao_ccpae';

    private function __construct(private readonly string $origem) {}

    public static function manual(): self
    {
        return new self(self::MANUAL);
    }

    /** So o PaeCcpaeService::emitir cria este contexto. */
    public static function emissaoCcpae(): self
    {
        return new self(self::EMISSAO_CCPAE);
    }

    public function ehEmissaoCcpae(): bool
    {
        return $this->origem === self::EMISSAO_CCPAE;
    }
}
