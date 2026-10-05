<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Exceptions;

final class TransicaoProibidaException extends \DomainException
{
    public static function entre(string $de, string $para): self
    {
        return new self("Transição inválida: {$de} → {$para}.");
    }

    public static function semEmissaoCcpae(): self
    {
        return new self('Para concluir o protocolo para CCPAE, use a emissão do CCPAE: informe o código e a data de emissão.');
    }
}
