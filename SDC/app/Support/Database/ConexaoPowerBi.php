<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Conexoes do sdc_powerbi: escrita (materializacao), leitura (API de
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
    use ConexaoSemFallback;

    public const CHAVE = 'powerbi_connection';

    public const VARIAVEL = 'DB_POWERBI_CONNECTION (ex.: pgsql_powerbi)';

    public const LEITURA = 'pgsql_powerbi_read';

    public const FONTE = 'pgsql_fonte_bi';

    /**
     * Nome da conexao de escrita; lanca se o recurso estiver desligado.
     */
    public static function nome(): string
    {
        return self::exigirAtiva();
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
