<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Etl;

use InvalidArgumentException;

/**
 * Tabelas do legado que entram na carga do nucleo de estoque, com as colunas
 * candidatas a identificar cada linha.
 *
 * O legado nao usa convencao unica de chave, e pior: a mesma tabela tem nomes
 * de chave diferentes conforme a base. aju_deposito e "id" no dbsdc (o dump de
 * producao) e "id_deposito" no gestaocedec_local. Por isso cada tabela declara
 * uma lista de candidatas em ordem de preferencia, e quem extrai escolhe a
 * primeira que existir na origem. Um mapa unico serve as duas bases sem exigir
 * dois caminhos de codigo.
 *
 * Classe pura de proposito: nao toca banco, e e o unico ponto que precisa mudar
 * quando uma tabela entra ou sai do escopo da carga.
 */
final class MapaTabelasLegado
{
    /** @var array<string, list<string>> */
    private const TABELAS = [
        'aju_unidade'       => ['id_unidade'],
        'aju_unidade_descr' => ['id_unid_descr'],
        'aju_deposito'      => ['id_deposito', 'id'],
        'aju_estoque'       => ['id_estoque'],
        'aju_baixa'         => ['id_baixa'],
        'aju_produto'       => ['id_produto'],
        'aju_transf'        => ['id_transf'],
        'aju_transferencia' => ['id_transferencia'],
        'aju_item_transf'   => ['id_item'],
        'aju_liberacao'     => ['id_liberacao'],
        'aju_item'          => ['id_item'],
        // A chave real e composta (id_pagamento, id_liberacao). id_pagamento
        // sozinho foi verificado como unico nas duas bases (producao: 3364 de
        // 3364). Se uma carga futura trouxer menos linhas do que a origem tem,
        // a premissa caiu e a chave precisa virar composta.
        'aju_pagamento'     => ['id_pagamento'],
        'aju_fonte'         => ['id'],
        'aju_fornecedores'  => ['id'],
        'aju_cfornecedor'   => ['id_fornecedor'],
        'aju_municipio'     => ['id_municipio'],
    ];

    /** @return array<string, list<string>> */
    public static function tabelas(): array
    {
        return self::TABELAS;
    }

    /** @return list<string> */
    public static function candidatasChave(string $tabela): array
    {
        return self::TABELAS[$tabela]
            ?? throw new InvalidArgumentException("Tabela fora do mapa de carga: {$tabela}");
    }

    /**
     * Escolhe a primeira coluna candidata presente na origem.
     *
     * @param  list<string>  $colunasDaOrigem
     */
    public static function resolverChave(string $tabela, array $colunasDaOrigem): ?string
    {
        foreach (self::candidatasChave($tabela) as $candidata) {
            if (in_array($candidata, $colunasDaOrigem, true)) {
                return $candidata;
            }
        }

        return null;
    }
}
