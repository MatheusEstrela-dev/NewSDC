<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Exceptions;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use LogicException;
use RuntimeException;

/**
 * O diretorio respondeu e recusou (ou a guarda de escopo barrou): definitiva,
 * o job encerra sem repetir. Mensagem fixa por codigo, sem `previous`.
 */
final class DiretorioRecusou extends RuntimeException
{
    public function __construct(private readonly CodigoErroDiretorio $codigo)
    {
        if ($codigo->indicaIndisponibilidade()) {
            throw new LogicException('Codigo de indisponibilidade usado como recusa: '.$codigo->value);
        }

        parent::__construct($codigo->mensagem());
    }

    public function codigo(): CodigoErroDiretorio
    {
        return $this->codigo;
    }
}
