<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Exceptions;

/**
 * Regra de negocio do video violada. Os controllers de video a traduzem em 422
 * JSON (o Handler global responde JSON sem consultar render() e esconde a
 * mensagem fora de debug).
 */
class OperacaoDeVideoInvalida extends \DomainException
{
    /** Erro esperado do usuario: nao vai para o log. */
    public function report(): bool
    {
        return true;
    }
}
