<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionAuditLog extends Model
{
    use HasFactory;

    protected $table = 'permission_audit_log';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'before_state',
        'after_state',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'created_at' => 'datetime',
    ];

    public const ACTION_ROLE_ASSIGNED = 'role.assigned';
    public const ACTION_ROLE_REMOVED = 'role.removed';
    public const ACTION_PERMISSION_ASSIGNED = 'permission.assigned';
    public const ACTION_PERMISSION_REMOVED = 'permission.removed';
    public const ACTION_USER_CREATED = 'user.created';
    public const ACTION_USER_UPDATED = 'user.updated';
    public const ACTION_USER_DELETED = 'user.deleted';
    public const ACTION_ROLE_CREATED = 'role.created';
    public const ACTION_ROLE_UPDATED = 'role.updated';
    public const ACTION_ROLE_DELETED = 'role.deleted';
    public const ACTION_PERMISSION_CREATED = 'permission.created';
    public const ACTION_ACCESS_DENIED = 'access.denied';
    public const ACTION_LOGIN_SUCCESS = 'login.success';
    public const ACTION_LOGIN_FAILED = 'login.failed';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_TOKEN_CREATED = 'token.created';
    public const ACTION_TOKEN_REVOKED = 'token.revoked';

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->ip_address) {
                $model->ip_address = request()->ip();
            }
            if (!$model->user_agent) {
                $model->user_agent = request()->userAgent();
            }
        });

        static::updating(function ($model) {
            throw new \Exception('Registros de auditoria sao imutaveis e nao podem ser atualizados.');
        });

        static::deleting(function ($model) {
            throw new \Exception('Registros de auditoria sao imutaveis e nao podem ser deletados.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logAction(
        int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $beforeState = null,
        ?array $afterState = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
        ]);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByEntity($query, string $entityType, ?int $entityId = null)
    {
        $query->where('entity_type', $entityType);

        if ($entityId !== null) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    public function scopeInDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
