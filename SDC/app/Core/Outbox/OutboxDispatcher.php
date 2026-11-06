<?php

declare(strict_types=1);

namespace App\Core\Outbox;

use App\Core\Events\DomainEvent;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Worker que despacha eventos pendentes do outbox para o sistema de eventos
 * do Laravel.
 *
 * A reserva permanece bloqueada ate a publicacao e a confirmacao. A sessao
 * da reserva e isolada das transacoes da aplicacao: listeners afterCommit
 * devem chegar a fila ANTES de dispatched_at ser confirmado.
 *
 * Entrega ao menos uma vez: falha depois da publicacao e antes do commit
 * pode repetir o evento. Consumidores precisam ser idempotentes.
 */
class OutboxDispatcher
{
    /** @var array<string, class-string<DomainEvent>> */
    private array $eventMap = [];

    /**
     * Registra o map FQN do evento para sua chave em event_name.
     *
     * @param  class-string<DomainEvent>  $class
     */
    public function register(string $eventName, int $version, string $class): self
    {
        $this->eventMap["{$eventName}@v{$version}"] = $class;

        return $this;
    }

    /**
     * Persiste um Domain Event no outbox.
     */
    public function persist(DomainEvent $event): OutboxEvent
    {
        return OutboxEvent::create([
            'id'             => $event->eventId,
            'event_name'     => $event->eventName(),
            'event_version'  => $event->eventVersion(),
            'aggregate_type' => $event->aggregateType,
            'aggregate_id'   => $event->aggregateId,
            'payload'        => $event->payload(),
            'metadata'       => $event->metadata,
            'occurred_at'    => $event->occurredAt,
        ]);
    }

    /**
     * Consome ate $batch eventos pendentes e publica via Event::dispatch.
     * Retorna numero de eventos efetivamente despachados.
     */
    public function dispatchPending(int $batch = 100): int
    {
        if ($batch < 1) {
            return 0;
        }

        $this->exigirForaDeTransacao();
        $retentativas = new OutboxRetryPolicy;
        $db = $this->conexaoDeReserva();
        $tentados = [];
        $count = 0;

        try {
            for ($i = 0; $i < $batch; $i++) {
                $resultado = $db->transaction(function () use ($db, $tentados, $retentativas): ?array {
                    $registro = $retentativas->elegiveis($db->table((new OutboxEvent)->getTable()))
                        ->whereNotIn('id', $tentados)
                        ->orderBy('occurred_at')->orderBy('id')
                        ->lock('FOR UPDATE SKIP LOCKED')
                        ->first();

                    if ($registro === null) {
                        return null;
                    }

                    // O modelo fornece casts; nunca salvar por ele, pois sua
                    // conexao normal nao e a sessao que detem a reserva.
                    $row = (new OutboxEvent)->newFromBuilder((array) $registro);
                    $erro = null;
                    try {
                        Event::dispatch($this->reidratar($row));
                    } catch (\Throwable $e) {
                        $erro = $e->getMessage();
                        Log::error('Outbox dispatch failed', [
                            'event_id' => $row->id,
                            'event_name' => $row->event_name,
                            'error' => $erro,
                        ]);
                    }

                    // Um listener nao pode deixar uma transacao pendente e
                    // adiar os proximos envios. Nesse caso a reserva reverte.
                    $this->exigirForaDeTransacao();

                    $db->table($row->getTable())->where('id', $row->id)->update([
                        'dispatched_at' => $erro === null ? now() : null,
                        'dispatch_attempts' => $row->dispatch_attempts + 1,
                        'last_error' => $erro,
                        'updated_at' => now(),
                    ]);

                    return [$row->id, $erro === null];
                });

                if ($resultado === null) {
                    break;
                }
                // Falha nao deve consumir o lote inteiro repetindo a mesma linha.
                $tentados[] = $resultado[0];
                $count += (int) $resultado[1];
            }
        } finally {
            $db->disconnect();
        }

        return $count;
    }

    private function exigirForaDeTransacao(): void
    {
        if (app('db.transactions')->getPendingTransactions()->isNotEmpty()) {
            throw new \LogicException('Despache o outbox fora das transacoes da aplicacao.');
        }

        foreach (DB::getConnections() as $connection) {
            $pdo = $connection->getRawPdo();
            if ($connection->transactionLevel() > 0 || ($pdo instanceof \PDO && $pdo->inTransaction())) {
                throw new \LogicException('Despache o outbox fora das transacoes da aplicacao.');
            }
        }
    }

    private function conexaoDeReserva(): Connection
    {
        // getConfig ja contem URL resolvida e o destino de escrita escolhido
        // pelo Laravel. Nao copiar read/write nem o roteamento de replicas.
        $config = (new OutboxEvent)->getConnection()->getConfig();
        unset($config['read'], $config['write'], $config['replica_routing']);
        $config['name'] = 'outbox_reserva';
        // Mesmo DSN com PDO persistente poderia reutilizar a sessao da
        // aplicacao e tornar a reserva visivel aos listeners.
        $config['options'][\PDO::ATTR_PERSISTENT] = false;

        // ConnectionFactory nao anexa db.transactions. Nao registrar esta
        // conexao no DatabaseManager: isso adiaria o envio a fila para depois
        // do commit da reserva e reabriria a janela de perda do evento.
        return app('db.factory')->make($config);
    }

    /**
     * Re-dispara eventos ja persistidos para um listener especifico (replay).
     * Util para reconstruir read-models/projecoes sem mexer no estado dos agregados.
     *
     * @param  callable(DomainEvent): void  $handler
     */
    public function replay(callable $handler, ?string $aggregateId = null, ?string $eventName = null): int
    {
        $query = OutboxEvent::query()->orderBy('occurred_at');
        if ($aggregateId) $query->where('aggregate_id', $aggregateId);
        if ($eventName) $query->where('event_name', $eventName);

        $count = 0;
        $query->lazyById(500)->each(function (OutboxEvent $row) use ($handler, &$count): void {
            $handler($this->reidratar($row));
            $count++;
        });

        return $count;
    }

    private function reidratar(OutboxEvent $row): DomainEvent
    {
        $key = "{$row->event_name}@v{$row->event_version}";
        $class = $this->eventMap[$key] ?? null;
        if (! $class || ! is_subclass_of($class, DomainEvent::class)) {
            throw new \RuntimeException("Domain Event nao registrado: {$key}");
        }

        return $class::fromOutbox([
            'id'             => $row->id,
            'aggregate_type' => $row->aggregate_type,
            'aggregate_id'   => $row->aggregate_id,
            'occurred_at'    => $row->occurred_at?->toIso8601String() ?? now()->toIso8601String(),
            'payload'        => $row->payload,
            'metadata'       => $row->metadata,
        ]);
    }
}
