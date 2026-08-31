<?php

declare(strict_types=1);

namespace App\Services\Search\Fontes;

use App\Services\Search\FonteSql;

/** Protocolos do PAE. Migrada do GlobalSearchService sem mudanca de consulta. */
class FontePae extends FonteSql
{
    public function chave(): string
    {
        return 'pae';
    }

    public function permissao(): ?string
    {
        return 'pae.protocolos.view';
    }

    protected function tabela(): string
    {
        return 'pae_protocolos';
    }

    protected function colunas(): array
    {
        return ['num_protocolo', 'sei_numero', 'sigibar', 'empnto_search'];
    }

    protected function selecionar(): array
    {
        return ['id', 'status'];
    }

    protected function linha(object $registro): array
    {
        return [
            'id' => $registro->id,
            'title' => $registro->num_protocolo,
            'subtitle' => $registro->sei_numero ? 'SEI: '.$registro->sei_numero : ($registro->sigibar ?? 'PAE'),
            'url' => route('pae.protocolos.index').'?search='.urlencode((string) $registro->num_protocolo),
            'icon' => 'document',
            'tag' => 'PAE',
        ];
    }
}
