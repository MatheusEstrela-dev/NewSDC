<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Exceptions;

/**
 * A tentativa da prova ja foi enviada: a escrita nao se aplica mais. Vira 422
 * com `codigo` estavel, para o front distinguir de erro de validacao.
 */
class TentativaEncerrada extends \DomainException
{
    public const CODIGO = 'tentativa_encerrada';

    public function __construct()
    {
        parent::__construct('Esta tentativa ja foi enviada.');
    }
}
