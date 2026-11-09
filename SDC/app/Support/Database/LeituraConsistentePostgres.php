<?php

declare(strict_types=1);

namespace App\Support\Database;

use Closure;
use Illuminate\Database\Connection;
use LogicException;
use PDO;
use Throwable;

/** Snapshot somente leitura, incluindo recuperacao da sessao em workers. */
final class LeituraConsistentePostgres
{
    /** O callback deve consumir seus iteraveis antes de devolver o resultado. */
    public static function executar(Connection $conexao, Closure $leitura): mixed
    {
        $pdo = $conexao->getRawPdo();
        if ($conexao->transactionLevel() > 0 || ($pdo instanceof PDO && $pdo->inTransaction())) {
            throw new LogicException('Inicie a leitura consistente fora de uma transacao da conexao.');
        }

        try {
            return $conexao->transaction(function () use ($conexao, $leitura): mixed {
                // Caracteristicas apenas desta transacao: nao vazam para o
                // proximo job. Configurar antes da primeira consulta.
                $conexao->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY');

                return $leitura();
            });
        } catch (Throwable $erro) {
            try {
                // Perda da sessao no commit pode deixar callbacks pendentes,
                // mesmo com transactionLevel() zerado pelo framework.
                $transacoes = app('db.transactions');
                if ($transacoes->getPendingTransactions()->contains('connection', $conexao->getName())) {
                    $transacoes->rollback($conexao->getName(), 0);
                }
            } finally {
                // PDO pode continuar dizendo inTransaction() apos a queda.
                // A proxima execucao deve abrir uma sessao e snapshot novos.
                $conexao->disconnect();
            }
            throw $erro;
        }
    }
}
