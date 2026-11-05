<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Conexoes do sdc_powerbi: escrita (entrada e materializacao), leitura (API de
 * BI no membro de BI) e fonte (leitura do sdc no mesmo membro).
 *
 * Diferente de ConexaoGeo, NAO ha fallback para a conexao default: sem
 * DB_POWERBI_CONNECTION o recurso esta desligado e escrita()/leitura()/fonte()
 * lancam, para que nada do dominio grave ou leia no sdc por engano.
 *
 * Nunca guardar a Connection devolvida: sob pool por coroutine ela vale so
 * para a coroutine que a obteve.
 */
final class ConexaoPowerBi
{
    public const CHAVE = 'powerbi_connection';

    public const LEITURA = 'pgsql_powerbi_read';

    public const FONTE = 'pgsql_fonte_bi';

    /**
     * Ligada so com a chave definida e apontando para fora da conexao default.
     */
    public static function ativa(): bool
    {
        $nome = (string) config('database.'.self::CHAVE);

        return $nome !== '' && $nome !== (string) config('database.default');
    }

    /**
     * Nome da conexao de escrita; lanca se o recurso estiver desligado.
     */
    public static function nome(): string
    {
        return self::exigirAtiva();
    }

    public static function exigirAtiva(): string
    {
        if (! self::ativa()) {
            throw new RuntimeException('sdc_powerbi desligado: defina DB_POWERBI_CONNECTION (ex.: pgsql_powerbi).');
        }

        return (string) config('database.'.self::CHAVE);
    }

    public static function escrita(): Connection
    {
        return DB::connection(self::exigirAtiva());
    }

    public static function leitura(): Connection
    {
        self::exigirAtiva();

        return DB::connection(self::LEITURA);
    }

    public static function fonte(): Connection
    {
        self::exigirAtiva();

        return DB::connection(self::FONTE);
    }

    public static function tabela(string $tabela): Builder
    {
        return self::escrita()->table($tabela);
    }
}
