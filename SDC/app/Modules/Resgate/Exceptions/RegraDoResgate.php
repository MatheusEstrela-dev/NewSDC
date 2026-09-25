<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Exceptions;

use DomainException;

/**
 * Operacao do resgate recusada por regra de negocio (quatro olhos, codigo
 * duplicado, saldo insuficiente, faixa abaixo...). O controller devolve como erro de
 * validacao, com a mensagem legivel.
 */
final class RegraDoResgate extends DomainException
{
    public function __construct(string $mensagem, public readonly string $campo = 'catalogo')
    {
        parent::__construct($mensagem);
    }
}
