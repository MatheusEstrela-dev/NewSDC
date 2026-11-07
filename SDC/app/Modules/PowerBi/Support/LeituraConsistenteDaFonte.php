<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Support\Database\ConexaoPowerBi;
use Closure;
use LogicException;
use PDO;
use Throwable;

/** Mantem a mesma visao da fonte entre paginas e consultas relacionadas. */
final class LeituraConsistenteDaFonte
{
    /** O callback deve consumir seus iteraveis antes de devolver o resultado. */
    public static function executar(Closure $leitura): mixed
    {
        $fonte = ConexaoPowerBi::fonte();
        $pdo = $fonte->getRawPdo();
        if ($fonte->transactionLevel() > 0 || ($pdo instanceof PDO && $pdo->inTransaction())) {
            throw new LogicException('Inicie a leitura consistente do BI fora de uma transacao da fonte.');
        }

        try {
            return $fonte->transaction(function () use ($fonte, $leitura): mixed {
                // Caracteristicas apenas desta transacao: nao vazam para o
                // proximo job. Configurar antes da primeira consulta.
                $fonte->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY');

                return $leitura();
            });
        } catch (Throwable $erro) {
            try {
                // Perda da sessao no commit pode deixar callbacks pendentes,
                // mesmo com transactionLevel() zerado pelo framework.
                $transacoes = app('db.transactions');
                if ($transacoes->getPendingTransactions()->contains('connection', $fonte->getName())) {
                    $transacoes->rollback($fonte->getName(), 0);
                }
            } finally {
                // PDO pode continuar dizendo inTransaction() apos a queda.
                // A proxima execucao deve abrir uma sessao e snapshot novos.
                $fonte->disconnect();
            }
            throw $erro;
        }
    }
}
