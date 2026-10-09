<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Closure;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Anotacao de uma pagina de protocolos com dados de um modulo, em lote: uma
 * consulta por pagina, nunca por protocolo.
 */
final class PaeListagem
{
    /**
     * @param  Closure(list<int>): mixed  $carregar  carrega o contexto de todos os ids da pagina
     * @param  Closure(int, mixed): array<string, mixed>  $campos  campos acrescentados a um protocolo
     */
    public static function anotar(LengthAwarePaginator $pagina, Closure $carregar, Closure $campos): LengthAwarePaginator
    {
        $ids = $pagina->getCollection()->map(fn ($item): int => (int) data_get($item, 'id'))->all();
        if ($ids === []) {
            return $pagina;
        }

        $contexto = $carregar($ids);
        $pagina->setCollection($pagina->getCollection()->map(fn ($item): array => [
            ...(is_array($item) ? $item : $item->toArray()),
            ...$campos((int) data_get($item, 'id'), $contexto),
        ]));

        return $pagina;
    }
}
