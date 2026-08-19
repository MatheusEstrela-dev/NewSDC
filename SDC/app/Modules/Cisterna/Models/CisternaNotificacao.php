<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Notificacao de fiscalizacao. Polimorfica: pende do beneficiario ou de uma
 * vistoria especifica. No legado so existia por cisterna_id, e o disparo era
 * um Mail::send para um Gmail pessoal hardcoded.
 */
class CisternaNotificacao extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'cisterna_notificacoes';

    protected $fillable = [
        'notificavel_type', 'notificavel_id',
        'observacao', 'respondida', 'respondida_em', 'created_by', 'legacy_id',
    ];

    protected $casts = [
        'respondida' => 'boolean',
        'respondida_em' => 'immutable_datetime',
        'legacy_id' => 'integer',
    ];

    public function notificavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('respondida', false);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documentos');
    }
}
