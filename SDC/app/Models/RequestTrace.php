<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trace de request assincrono. Criado pelo trait AsynchronousResponse,
 * atualizado pelo job via TracksAsyncProgress, consultado pelo cliente
 * via GET /api/v1/traces/{id}.
 */
class RequestTrace extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'status',
        'result_disk',
        'result_path',
        'meta',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(?string $disk = null, ?string $path = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'result_disk' => $disk,
            'result_path' => $path,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function hasResult(): bool
    {
        return $this->isCompleted() && $this->result_disk && $this->result_path;
    }
}
