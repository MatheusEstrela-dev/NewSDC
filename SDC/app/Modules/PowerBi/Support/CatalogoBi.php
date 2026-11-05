<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Modules\PowerBi\Contracts\Materializador;

/** Registro dos materializadores e das tabelas expostas (whitelist). */
final class CatalogoBi
{
    /** @return list<string> */
    public function modulos(): array
    {
        return array_keys((array) config('powerbi.materializadores', []));
    }

    public function materializador(string $modulo): ?Materializador
    {
        $classe = config('powerbi.materializadores.'.$modulo);

        return is_string($classe) && $classe !== '' ? app($classe) : null;
    }

    /** Definicao de uma tabela do catalogo; null se o modulo ou a tabela nao estao registrados. */
    public function definicao(string $modulo, string $tabela): ?DefinicaoTabela
    {
        if (! in_array($modulo, $this->modulos(), true)) {
            return null;
        }
        $c = config("powerbi.catalogo.{$modulo}.{$tabela}");

        return is_array($c) ? DefinicaoTabela::dosConfig($modulo, $tabela, $c) : null;
    }
}
