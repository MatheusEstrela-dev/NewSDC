<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Exceptions;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use Illuminate\Validation\ValidationException;

/**
 * Pedido recusado localmente, antes de qualquer operacao ou job. Filha de
 * ValidationException (como RemanejamentoProibido): o handler do Laravel ja
 * devolve 422 (JSON) ou erro de sessao (Inertia) na chave `diretorio`.
 */
final class OperacaoDiretorioProibida extends ValidationException
{
    private const CHAVE = 'diretorio';

    public static function propriaConta(): self
    {
        return self::comMensagem('Você não pode executar ações do diretório sobre a sua própria conta.');
    }

    public static function contaProtegida(): self
    {
        return self::comMensagem(CodigoErroDiretorio::CONTA_PROTEGIDA->mensagem());
    }

    public static function semLogin(): self
    {
        return self::comMensagem('O cadastro não tem login do AD informado.');
    }

    public static function motivoObrigatorio(): self
    {
        return self::comMensagem('Informe o motivo para esta ação.');
    }

    public static function loginInvalido(): self
    {
        return self::comMensagem('Login do AD inválido.');
    }

    private static function comMensagem(string $mensagem): self
    {
        return self::withMessages([self::CHAVE => $mensagem]);
    }
}
