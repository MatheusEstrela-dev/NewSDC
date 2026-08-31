<?php

declare(strict_types=1);

namespace App\Services\Search\Fontes;

use App\Services\Search\FonteSql;

/**
 * Municipios de Minas Gerais.
 *
 * Sem permissao propria: a caixa ja anuncia "municipio" e o catalogo do IBGE e
 * publico -- exigir slug aqui esconderia de quase todo mundo um dado que nao e
 * sigiloso. A linha leva a lista de cisternas do municipio, que e o uso real.
 */
class FonteMunicipios extends FonteSql
{
    public function chave(): string
    {
        return 'municipios';
    }

    protected function tabela(): string
    {
        return 'municipios';
    }

    protected function colunas(): array
    {
        return ['nome', 'codigo_ibge'];
    }

    protected function selecionar(): array
    {
        return ['id', 'uf'];
    }

    protected function linha(object $registro): array
    {
        return [
            'id' => $registro->id,
            'title' => $registro->nome.' / '.$registro->uf,
            'subtitle' => 'IBGE '.$registro->codigo_ibge,
            'url' => route('cisternas.beneficiarios.index').'?municipio_id='.$registro->id,
            'icon' => 'building',
            'tag' => 'MUNICIPIO',
        ];
    }
}
