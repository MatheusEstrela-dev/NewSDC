<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Domain\Entities;

use App\Modules\Plantao\Domain\ValueObjects\PeriodoPlantao;
use App\Modules\Plantao\Domain\ValueObjects\StatusPlantao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity: Plantão
 *
 * Representa um turno de plantão no sistema de Defesa Civil.
 */
class Plantao extends Model
{
    protected $table = 'plantoes';

    protected $fillable = [
        'data',
        'plantonista_id',
        'plantonista_nome',
        'periodo',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data' => 'date',
        'status' => StatusPlantao::class,
        'periodo' => PeriodoPlantao::class,
    ];

    /**
     * Relacionamento com o usuário plantonista
     */
    public function plantonista(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'plantonista_id');
    }
}
