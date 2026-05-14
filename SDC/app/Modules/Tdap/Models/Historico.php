<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int             $id
 * @property \Carbon\Carbon  $data_evento
 * @property string          $tipo_evento
 * @property string          $entity_type
 * @property int             $entity_id
 * @property ?int            $user_id
 * @property ?string         $obs
 * @property ?array          $payload
 */
class Historico extends Model
{
    protected $table = 'tdap_historicos';

    protected $fillable = [
        'data_evento',
        'tipo_evento',
        'entity_type',
        'entity_id',
        'user_id',
        'obs',
        'payload',
    ];

    protected $casts = [
        'data_evento' => 'datetime',
        'entity_id'   => 'integer',
        'user_id'     => 'integer',
        'payload'     => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeDoEntity(Builder $query, string $entityType, int $entityId): Builder
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeDoTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo_evento', $tipo);
    }
}
