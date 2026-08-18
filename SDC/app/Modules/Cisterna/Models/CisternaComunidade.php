<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\Municipio;
use Database\Factories\Cisterna\CisternaComunidadeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CisternaComunidade extends Model
{
    use HasFactory;

    protected $table = 'cisterna_comunidades';

    protected $fillable = ['municipio_id', 'nome', 'ativa', 'legacy_id'];

    protected $casts = [
        'ativa' => 'boolean',
        'legacy_id' => 'integer',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function beneficiarios(): HasMany
    {
        return $this->hasMany(CisternaBeneficiario::class, 'comunidade_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }

    protected static function newFactory(): CisternaComunidadeFactory
    {
        return CisternaComunidadeFactory::new();
    }
}
