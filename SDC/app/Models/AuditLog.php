<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'table_name',
        'row_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public const EVENT_INSERT = 'insert';
    public const EVENT_UPDATE = 'update';
    public const EVENT_DELETE = 'delete';
    public const EVENT_LOGIN = 'login';
    public const EVENT_LOGOUT = 'logout';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $event,
        string $tableName,
        ?int $rowId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): self {
        return self::create([
            'user_id' => $userId ?? Auth::id(),
            'event' => $event,
            'table_name' => $tableName,
            'row_id' => $rowId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    public static function logInsert(string $tableName, int $rowId, array $newValues): self
    {
        return self::log(self::EVENT_INSERT, $tableName, $rowId, null, $newValues);
    }

    public static function logUpdate(string $tableName, int $rowId, array $oldValues, array $newValues): self
    {
        return self::log(self::EVENT_UPDATE, $tableName, $rowId, $oldValues, $newValues);
    }

    public static function logDelete(string $tableName, int $rowId, array $oldValues): self
    {
        return self::log(self::EVENT_DELETE, $tableName, $rowId, $oldValues, null);
    }

    public static function logLogin(?int $userId = null): self
    {
        return self::log(self::EVENT_LOGIN, 'users', $userId ?? Auth::id(), null, null, $userId);
    }

    public static function logLogout(?int $userId = null): self
    {
        return self::log(self::EVENT_LOGOUT, 'users', $userId ?? Auth::id(), null, null, $userId);
    }
}
