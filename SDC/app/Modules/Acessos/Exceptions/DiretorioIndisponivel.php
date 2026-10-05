<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Exceptions;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use LogicException;
use RuntimeException;

/**
 * O diretorio nao respondeu sobre a conta: rede, tempo, conexao ou ambiente
 * sem configuracao. Se o codigo e transitorio o job repete. A mensagem e fixa
 * por codigo e o construtor nao aceita `previous`: a excecao original (com o
 * diagnostico do servidor LDAP) nunca e encadeada.
 */
final class DiretorioIndisponivel extends RuntimeException
{
    public function __construct(private readonly CodigoErroDiretorio $codigo = CodigoErroDiretorio::INDISPONIVEL)
    {
        if (! $codigo->indicaIndisponibilidade()) {
            throw new LogicException('Codigo de recusa usado como indisponibilidade: '.$codigo->value);
        }

        parent::__construct($codigo->mensagem());
    }

    public function codigo(): CodigoErroDiretorio
    {
        return $this->codigo;
    }
}
