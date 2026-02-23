<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class UserStatusHistory extends Model
{
    protected $fillable = [
        'user_id',
        'old_status',
        'new_status',
        'reason',
        'changed_by',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public static function logStatusChange(
        User $user,
        string $oldStatus,
        string $newStatus,
        ?string $reason = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => Auth::id(),
            'ip_address' => Request::ip(),
        ]);
    }
}
