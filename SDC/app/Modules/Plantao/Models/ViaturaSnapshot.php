<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaturaSnapshot extends Model
{
    protected $table = 'plantao_viatura_snapshots';

    protected $fillable = [
        'plantao_id',
        'viatura_id',
        'prefixo',
        'placa',
        'hodometro',
        'nivel_combustivel',
        'alteracoes',
        'ultimo_condutor_id',
        'ultimo_condutor_nome',
        'anotacao',
        'em_condicoes',
    ];

    protected $casts = [
        'nivel_combustivel' => NivelCombustivel::class,
        'hodometro' => 'integer',
        'em_condicoes' => 'boolean',
    ];

    public function plantao(): BelongsTo
    {
        return $this->belongsTo(Plantao::class, 'plantao_id');
    }

    public function viatura(): BelongsTo
    {
        return $this->belongsTo(Viatura::class, 'viatura_id');
    }

    public function ultimoCondutor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'ultimo_condutor_id');
    }
}
