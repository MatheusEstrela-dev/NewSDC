<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Support\Database\ConexaoSeparada;
use Closure;
use Illuminate\Database\Connection;

/**
 * Ponto unico da conexao do Treinamento para codigo que nao passa pelo model
 * (transacoes, DB::table, comandos). Resolvida a cada uso: sob pool por
 * coroutine a Connection vale so para quem a obteve.
 */
final class ConexaoTreinamento
{
    private const CHAVE = 'treinamento_connection';

    public static function conexao(): Connection
    {
        return ConexaoSeparada::de(self::CHAVE)->conexao();
    }

    public static function nome(): string
    {
        return ConexaoSeparada::de(self::CHAVE)->nome();
    }

    /**
     * Transacao na conexao do Treinamento: o cadastro do cidadao e o pedido de
     * verificacao vivem la, e uma transacao na conexao default nao os cobriria.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function transacao(Closure $callback): mixed
    {
        return self::conexao()->transaction($callback);
    }
}
