<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Support;

/**
 * Marca que a escrita em curso e carga do legado, nao acao de usuario.
 *
 * Registrado como scoped: sob Octane o estado morre com a requisicao, entao uma
 * importacao nunca silencia notificacao de outra requisicao do mesmo worker.
 */
final class ContextoImportacao
{
    private bool $ativo = false;

    public function ativo(): bool
    {
        return $this->ativo;
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function durante(callable $fn): mixed
    {
        $anterior = $this->ativo;
        $this->ativo = true;
        try {
            return $fn();
        } finally {
            $this->ativo = $anterior;
        }
    }
}
