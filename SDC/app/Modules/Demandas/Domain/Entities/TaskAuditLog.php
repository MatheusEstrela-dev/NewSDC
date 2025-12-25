<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit Log: Registro Imutável de Alterações
 *
 * Não possui updated_at nem deleted_at para garantir imutabilidade
 */
class TaskAuditLog extends Model
{
    protected $table = 'task_audit_logs';

    public $timestamps = false; // Apenas created_at

    protected $fillable = [
        'task_id',
        'user_id',
        'acao',
        'campo',
        'valor_anterior',
        'valor_novo',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Garantir que created_at seja sempre setado
        static::creating(function (TaskAuditLog $log) {
            if (! $log->created_at) {
                $log->created_at = now();
            }
        });

        // Impedir updates (imutabilidade)
        static::updating(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be updated');
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
