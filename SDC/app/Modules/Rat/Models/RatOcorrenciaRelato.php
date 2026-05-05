<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RatOcorrenciaRelato extends Model
{
    use SoftDeletes;

    protected $table = 'rat_ocorrencia_relatos';

    protected $fillable = [
        'ocorrencia_id',
        'conteudo_id',
        'conteudo_type',
        'created_by',
    ];

    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }

    public function conteudo(): MorphTo
    {
        return $this->morphTo('conteudo');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $relato): void {
            $relato->conteudo?->delete();
        });
    }
}
