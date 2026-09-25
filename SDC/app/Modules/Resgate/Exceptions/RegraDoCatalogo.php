<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Exceptions;

use DomainException;

/**
 * Operacao no catalogo recusada por regra de negocio (quatro olhos, codigo
 * duplicado, proposta ja decidida...). O controller devolve como erro de
 * validacao, com a mensagem legivel.
 */
final class RegraDoCatalogo extends DomainException
{
    public function __construct(string $mensagem, public readonly string $campo = 'catalogo')
    {
        parent::__construct($mensagem);
    }
}
