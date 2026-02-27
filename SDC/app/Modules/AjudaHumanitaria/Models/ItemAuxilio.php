<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model: Item de Auxilio
 *
 * Representa um item especifico dentro de um auxilio (kit)
 */
class ItemAuxilio extends Model
{
    use HasFactory;

    protected $table = 'itens_auxilio';

    protected $fillable = [
        'auxilio_id',
        'descricao',
        'quantidade',
        'unidade_medida',
        'valor_unitario',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'valor_unitario' => 'decimal:2',
    ];

    /**
     * Relacionamento: Auxilio pai
     */
    public function auxilio(): BelongsTo
    {
        return $this->belongsTo(Auxilio::class, 'auxilio_id');
    }
}
