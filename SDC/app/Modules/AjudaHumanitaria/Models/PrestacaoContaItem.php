<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrestacaoContaItem extends Model
{
    protected $table = 'prestacao_conta_itens';

    protected $fillable = [
        'prestacao_conta_id',
        'material_ah_id',
        'codigo_material',
        'nome_material',
        'qtd',
        'total_familia_atendida',
    ];

    protected $casts = [
        'qtd'                    => 'integer',
        'total_familia_atendida' => 'integer',
    ];

    public function prestacaoConta(): BelongsTo
    {
        return $this->belongsTo(PrestacaoConta::class, 'prestacao_conta_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MaterialAh::class, 'material_ah_id');
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(PrestacaoContaEntrega::class, 'prestacao_conta_item_id');
    }
}
