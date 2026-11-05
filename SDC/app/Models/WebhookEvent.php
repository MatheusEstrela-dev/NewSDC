<?php

namespace App\Models;

use App\Support\Database\UsaConexaoDeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Model para eventos de webhook com suporte a idempotencia
 * Garante que o mesmo evento nao seja processado duas vezes
 *
 * Isolamento de conexao pela conexao de log (UsaConexaoDeLog), que substituiu a
 * antiga pgsql_webhook. O log:podar apaga, depois de 90 dias, so o evento
 * encerrado (completed ou dead_letter); pendente e falho seguem retentaveis.
 */
class WebhookEvent extends Model
{
    use HasUuids;
    use UsaConexaoDeLog;

    /**
     * Insercao direta, sem o adiamento e a fila de retentativa da trait.
     *
     * Aqui a linha e estado de idempotencia, e nao so registro: o
     * firstOrCreate do ProcessInboundWebhook precisa da chave na volta para o
     * claimForProcessing, e precisa ver a violacao de unicidade para resolver a
     * corrida entre entregas. Engolir a falha e mandar para a fila perderia o
     * evento em silencio. O evento nasce no worker, fora de transacao critica,
     * entao nao ha o que adiar.
     */
    public function save(array $options = []): bool
    {
        return parent::save($options);
    }

    protected $fillable = [
        'external_event_id',
        'provider',
        'event_type',
        'payload',
        'signature',
        'status',
        'attempts',
        'processed_at',
        'last_attempt_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEAD_LETTER = 'dead_letter';

    /**
     * Verifica se o evento ja foi processado ou esta em processamento
     * Usado para idempotencia
     */
    public function isProcessedOrProcessing(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Adquire o evento em uma unica escrita condicional. O indice unico
     * protege a identidade; este UPDATE protege a execucao concorrente.
     */
    public function claimForProcessing(int $timeout): bool
    {
        $claimed = $this->newQuery()->whereKey($this->getKey())
            ->where(function ($query) use ($timeout) {
                $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED])
                    ->orWhere(function ($query) use ($timeout) {
                        $query->where('status', self::STATUS_PROCESSING)
                            ->where(function ($query) use ($timeout) {
                                $query->whereNull('last_attempt_at')
                                    ->orWhere('last_attempt_at', '<=', now()->subSeconds($timeout + 5));
                            });
                    });
            })
            ->update([
                'status' => self::STATUS_PROCESSING,
                'attempts' => $this->getConnection()->raw('attempts + 1'),
                'last_attempt_at' => now(),
                'error_message' => null,
            ]);

        $this->refresh();

        return $claimed === 1;
    }

    /**
     * Marca evento como em processamento
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'attempts' => $this->attempts + 1,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Marca evento como concluido com sucesso
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Marca evento como falho
     * Move para dead_letter se exceder max retries
     */
    public function markAsFailed(string $error): void
    {
        $maxRetries = config('webhooks.max_retries', 4);

        $status = $this->attempts >= $maxRetries
            ? self::STATUS_DEAD_LETTER
            : self::STATUS_FAILED;

        $this->update([
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    /**
     * Reseta para pending (para reprocessamento manual)
     */
    public function resetToPending(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
            'error_message' => null,
        ]);
    }

    /**
     * Scope para eventos pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para eventos falhos (nao dead letter)
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope para dead letter queue
     */
    public function scopeDeadLetter($query)
    {
        return $query->where('status', self::STATUS_DEAD_LETTER);
    }

    /**
     * Scope por provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
