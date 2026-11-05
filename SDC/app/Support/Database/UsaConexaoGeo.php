<?php

declare(strict_types=1);

namespace App\Support\Database;

/**
 * Model do geoespacial (medalhao: meteorologia, sismo, hidro, camadas):
 * conexao database.geo_connection. Leitura pesada de mapa usa
 * ConexaoGeo::leitura() direto no repositorio.
 */
trait UsaConexaoGeo
{
    use UsaConexaoSeparada;

    protected static function chaveDeConexao(): string
    {
        return ConexaoGeo::CHAVE;
    }
}
