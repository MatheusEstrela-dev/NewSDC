<?php

declare(strict_types=1);

namespace App\Services\Search\Fontes;

use App\Services\Search\FonteSql;

/** Demandas (tasks). */
class FonteDemandas extends FonteSql
{
    public function chave(): string
    {
        return 'demandas';
    }

    public function permissao(): ?string
    {
        return 'demandas.view';
    }

    protected function tabela(): string
    {
        return 'tasks';
    }

    protected function colunas(): array
    {
        return ['titulo', 'protocolo'];
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
            'title' => $registro->titulo,
            'subtitle' => trim(($registro->protocolo ?? '').' · '.($registro->status ?? ''), ' ·'),
            'url' => route('demandas.show', $registro->id),
            'icon' => 'checkbadge',
            'tag' => 'DEMANDA',
        ];
    }
}
