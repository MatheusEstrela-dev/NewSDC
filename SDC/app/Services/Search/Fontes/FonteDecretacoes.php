<?php

declare(strict_types=1);

namespace App\Services\Search\Fontes;

use App\Services\Search\FonteSql;

/** Processos de reconhecimento de desastre. */
class FonteDecretacoes extends FonteSql
{
    public function chave(): string
    {
        return 'decretacoes';
    }

    public function permissao(): ?string
    {
        return 'decretacoes.processos.view';
    }

    protected function tabela(): string
    {
        return 'processos';
    }

    protected function colunas(): array
    {
        return ['n_protocolo_fide', 'tipo_desastre_nome'];
    }

    protected function selecionar(): array
    {
        return ['id'];
    }

    protected function filtroAdicional(array &$bindings): string
    {
        return 'deleted_at IS NULL';
    }

    protected function linha(object $registro): array
    {
        return [
            'id' => $registro->id,
            'title' => $registro->n_protocolo_fide ?? '—',
            'subtitle' => $registro->tipo_desastre_nome ?? 'Decretacao',
            'url' => route('decretacoes.show', $registro->id),
            'icon' => 'scale',
            'tag' => 'DECRETO',
        ];
    }
}
