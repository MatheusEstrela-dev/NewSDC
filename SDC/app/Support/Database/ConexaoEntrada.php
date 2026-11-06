<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Conexao do sdc_entrada: caixa de entrada assincrona da API externa.
 *
 * Fica no cluster do sdc (lider + standby sincrono), separada do sdc_powerbi:
 * a mensagem e o unico dado da integracao que nao se reconstroi, e o 202 so
 * vale se ela estiver gravada com a mesma durabilidade do sdc.
 *
 * Sem DB_ENTRADA_CONNECTION a caixa esta desligada e as rotas seguem
 * sincronas. Nunca guardar a Connection devolvida (pool por coroutine).
 */
final class ConexaoEntrada
{
    use ConexaoSemFallback;

    public const CHAVE = 'entrada_connection';

    public const VARIAVEL = 'DB_ENTRADA_CONNECTION (ex.: pgsql_entrada)';

    public static function conexao(): Connection
    {
        return DB::connection(self::exigirAtiva());
    }
}
