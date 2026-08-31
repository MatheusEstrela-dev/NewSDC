<?php

declare(strict_types=1);

namespace App\Services\Search\Fontes;

use App\Services\Search\FonteSql;

/**
 * Ocorrencias do RAT.
 *
 * A tabela real e rat_ocorrencias -- o schema legado 'rats' nao existe neste
 * banco -- e o identificador pesquisavel e numero_bos.
 */
class FonteRat extends FonteSql
{
    public function chave(): string
    {
        return 'rat';
    }

    public function permissao(): ?string
    {
        return 'rat.ocorrencias.view';
    }

    protected function tabela(): string
    {
        return 'rat_ocorrencias';
    }

    protected function colunas(): array
    {
        return ['numero_bos'];
    }

    protected function selecionar(): array
    {
        return ['id', 'status'];
    }

    protected function filtroAdicional(array &$bindings): string
    {
        return 'deleted_at IS NULL';
    }

    protected function linha(object $registro): array
    {
        return [
            'id' => $registro->id,
            'title' => $registro->numero_bos,
            'subtitle' => ucfirst((string) ($registro->status ?? 'RAT')),
            'url' => route('rat.show', $registro->id),
            'icon' => 'document',
            'tag' => 'RAT',
        ];
    }
}
