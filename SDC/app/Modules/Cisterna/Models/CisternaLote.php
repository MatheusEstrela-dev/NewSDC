<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use Database\Factories\Cisterna\CisternaLoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CisternaLote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cisterna_lotes';

    protected $fillable = ['nome', 'data', 'observacao', 'legacy_id'];

    protected $casts = [
        'data' => 'date',
        'legacy_id' => 'integer',
    ];

    public function ordensServico(): HasMany
    {
        return $this->hasMany(CisternaOrdemServico::class, 'lote_id');
    }

    protected static function newFactory(): CisternaLoteFactory
    {
        return CisternaLoteFactory::new();
    }
}
