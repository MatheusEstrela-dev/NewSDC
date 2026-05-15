<?php

declare(strict_types=1);

namespace App\Core\Outbox;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string             $id
 * @property string             $event_name
 * @property int                $event_version
 * @property string             $aggregate_type
 * @property string             $aggregate_id
 * @property array              $payload
 * @property ?array             $metadata
 * @property \Carbon\Carbon     $occurred_at
 * @property ?\Carbon\Carbon    $dispatched_at
 * @property int                $dispatch_attempts
 * @property ?string            $last_error
 */
class OutboxEvent extends Model
{
    protected $table = 'outbox_events';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'event_name',
        'event_version',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'metadata',
        'occurred_at',
        'dispatched_at',
        'dispatch_attempts',
        'last_error',
    ];

    protected $casts = [
        'event_version'     => 'integer',
        'payload'           => 'array',
        'metadata'          => 'array',
        'occurred_at'       => 'datetime',
        'dispatched_at'     => 'datetime',
        'dispatch_attempts' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (! $model->id) {
                $model->id = (string) Str::uuid();
            }
            if (! $model->occurred_at) {
                $model->occurred_at = now();
            }
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('dispatched_at');
    }

    public function scopeDoAggregate(Builder $query, string $type, string $id): Builder
    {
        return $query->where('aggregate_type', $type)->where('aggregate_id', $id);
    }
}
