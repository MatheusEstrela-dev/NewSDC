<?php

declare(strict_types=1);

namespace App\Services\Search\Fontes;

use App\Services\Search\FonteSql;

/** Orgaos: COMPDEC e parceiros. Codigo e nome sao o que se procura. */
class FonteOrgaos extends FonteSql
{
    public function chave(): string
    {
        return 'orgaos';
    }

    public function permissao(): ?string
    {
        return 'compdec.orgaos.view';
    }

    protected function tabela(): string
    {
        return 'compdec_orgaos';
    }

    protected function colunas(): array
    {
        return ['nome', 'codigo'];
    }

    protected function selecionar(): array
    {
        return ['id', 'tipo'];
    }

    protected function filtroAdicional(array &$bindings): string
    {
        return 'deleted_at IS NULL';
    }

    protected function linha(object $registro): array
    {
        return [
            'id' => $registro->id,
            'title' => $registro->nome,
            'subtitle' => trim(($registro->codigo ?? '').' · '.strtoupper((string) ($registro->tipo ?? '')), ' ·'),
            'url' => route('compdec.index').'?search='.urlencode((string) $registro->codigo),
            'icon' => 'building',
            'tag' => 'ORGAO',
        ];
    }
}
