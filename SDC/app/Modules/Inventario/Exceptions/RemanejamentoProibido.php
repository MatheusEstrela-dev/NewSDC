<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Regra de dominio do lote violada. Filha de ValidationException de proposito:
 * o handler do Laravel ja devolve 422 (JSON) ou erros de sessao (Inertia) por
 * chave, entao cada item recusado chega ao campo certo do formulario sem
 * try/catch nos controllers.
 */
final class RemanejamentoProibido extends ValidationException
{
    /**
     * Uma mensagem por chave: o Inertia so repassa a primeira mensagem de cada
     * campo, entao os itens recusados do mesmo campo seguem juntos.
     *
     * @param array<string, string|list<string>> $erros
     */
    public static function comErros(array $erros): self
    {
        return self::withMessages(array_map(
            static fn (string|array $mensagens): string => is_array($mensagens) ? implode(' ', $mensagens) : $mensagens,
            $erros,
        ));
    }

    public static function loteDesfeito(): self
    {
        return self::withMessages(['remanejamento' => 'Lote já desfeito.']);
    }

    public static function configuracao(string $mensagem): self
    {
        return self::withMessages(['configuracao' => $mensagem]);
    }
}
