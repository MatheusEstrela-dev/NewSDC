<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integration extends Model
{
    protected $fillable = [
        'integration_id',
        'type',
        'action',
        'endpoint',
        'payload',
        'response',
        'duration_ms',
        'success',
        'user_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'success' => 'boolean',
        'duration_ms' => 'float',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
