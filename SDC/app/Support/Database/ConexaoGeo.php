<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Conexoes do geoespacial (banco sdc_geoespacial): escrita para ingestao,
 * upsert silver e REFRESH das matviews; leitura para os mapas e telas.
 *
 * Com DB_GEO_CONNECTION desligada as duas caem na conexao default (sdc), onde
 * os mesmos schemas por dominio existem: o codigo usa sempre nomes
 * qualificados (DominioGeo) e funciona nos dois lugares.
 */
final class ConexaoGeo
{
    public const CHAVE = 'geo_connection';

    public const LEITURA = 'pgsql_geo_read';

    public static function nome(): string
    {
        return ConexaoSeparada::de(self::CHAVE)->nome();
    }

    public static function separada(): bool
    {
        return ConexaoSeparada::de(self::CHAVE)->separada();
    }

    public static function conexao(): Connection
    {
        return ConexaoSeparada::de(self::CHAVE)->conexao();
    }

    /**
     * Conexao de SELECT dos mapas. So existe separada da escrita quando o geo
     * esta no banco proprio; antes disso le pela mesma conexao da escrita.
     */
    public static function leitura(): Connection
    {
        return self::separada() ? DB::connection(self::LEITURA) : self::conexao();
    }

    public static function tabela(string $tabela): Builder
    {
        return self::conexao()->table($tabela);
    }
}
