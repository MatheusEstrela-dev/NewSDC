<?php

declare(strict_types=1);

namespace App\Support\Database;

use PDO;

/**
 * Contrato de um pool de PDO por worker, emprestado por coroutine.
 *
 * Existe para que o CoroutineDatabaseManager e o PdoPoolRegistry dependam de
 * uma abstracao e nao do SwoolePdoPool: assim cada conexao listada em
 * database.coroutine_pools ganha o seu pool, e os testes trocam o Channel do
 * Swoole por um pool em memoria.
 */
interface PdoPool
{
    /**
     * Empresta um PDO. Esgotado o prazo, lanca PoolEsgotado com label().
     * $timeout substitui o prazo do pool nesta chamada (leitura usa prazo curto).
     */
    public function acquire(?float $timeout = null): PDO;

    /**
     * Devolve o PDO limpo ao pool (rollback de transacao esquecida).
     */
    public function release(PDO $pdo): void;

    /**
     * Libera o slot sem devolver PDO: a conexao emprestada foi perdida.
     */
    public function discard(): void;

    /**
     * Pre-cria as conexoes ate o teto. Falha interrompe sem derrubar.
     */
    public function warm(): void;

    public function capacity(): int;

    public function created(): int;

    public function available(): int;

    public function timeout(): float;

    /**
     * Nome do pool no PoolEsgotado e no diagnostico (ex.: pgsql, pgsql_log).
     */
    public function label(): string;
}
