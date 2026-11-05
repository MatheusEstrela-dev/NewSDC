<?php

namespace App\Listeners\Queue;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\QueryExecuted;

/**
 * Descarta o PDO de conexoes que ficaram ociosas no worker de fila.
 *
 * O HAProxy do Patroni derruba sessoes sem trafego (timeout client/server) e o
 * ConsistentPostgresConnection nao repete a query apos perder a conexao (uma
 * escrita sem resposta nao pode ser repetida as cegas). Resultado: a primeira
 * query depois de uma pausa longa falhava com "server closed the connection".
 * Aqui a conexao ociosa e descartada ANTES do job; a proxima query reabre.
 */
class DesconectaConexoesOciosas
{
    /** @var array<string, float> ultima atividade por conexao */
    private array $ultimaAtividade = [];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly int $limiteSegundos,
    ) {}

    public function registrarAtividade(QueryExecuted $evento): void
    {
        $this->ultimaAtividade[$evento->connectionName] = microtime(true);
    }

    /** Chamado em Looping e JobProcessing; custo = um foreach sobre poucas conexoes. */
    public function handle(?object $evento = null): void
    {
        $agora = microtime(true);

        foreach ($this->ultimaAtividade as $nome => $ultima) {
            if ($agora - $ultima < $this->limiteSegundos) {
                continue;
            }

            unset($this->ultimaAtividade[$nome]);
            $conexao = $this->db->connection($nome);

            // Transacao aberta nunca e interrompida aqui.
            if ($conexao->transactionLevel() === 0) {
                $conexao->disconnect();
            }
        }
    }
}
