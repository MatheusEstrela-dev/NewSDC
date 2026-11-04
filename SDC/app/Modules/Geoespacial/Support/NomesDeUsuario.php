<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Support;

use App\Models\User;

/**
 * Resolve nome de usuario em duas etapas: a consulta geo traz so os ids
 * (users vive no sdc, outro banco quando o geo e separado) e aqui os nomes
 * vem numa segunda consulta, uma por lote e nao por linha.
 */
final class NomesDeUsuario
{
    /**
     * Preenche, em cada linha, os campos de nome a partir dos campos de id.
     *
     * @param iterable<object> $linhas
     * @param array<string, string> $campos coluna de id => coluna de nome a criar
     */
    public static function anexar(iterable $linhas, array $campos): void
    {
        $ids = [];

        foreach ($linhas as $linha) {
            foreach (array_keys($campos) as $colunaId) {
                if ($linha->{$colunaId} !== null) {
                    $ids[(int) $linha->{$colunaId}] = true;
                }
            }
        }

        $nomes = $ids === []
            ? []
            : User::withTrashed()->whereIn('id', array_keys($ids))->pluck('name', 'id')->all();

        foreach ($linhas as $linha) {
            foreach ($campos as $colunaId => $colunaNome) {
                $id = $linha->{$colunaId};
                $linha->{$colunaNome} = $id !== null ? ($nomes[(int) $id] ?? null) : null;
            }
        }
    }
}
