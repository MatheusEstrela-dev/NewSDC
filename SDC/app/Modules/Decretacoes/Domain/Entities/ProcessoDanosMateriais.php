<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity: Danos Materiais por Município
 */
class ProcessoDanosMateriais extends Model
{
    protected $table = 'processo_danos_materiais';

    protected $fillable = [
        'processo_id',
        'municipio_id',
        'tipo_bem',
        'quantidade_destruida',
        'quantidade_danificada',
        'valor_estimado',
        'observacoes',
    ];

    protected $casts = [
        'quantidade_destruida' => 'integer',
        'quantidade_danificada' => 'integer',
        'valor_estimado' => 'decimal:2',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class);
    }
}
