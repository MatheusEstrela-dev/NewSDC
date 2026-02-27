<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusPlantao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function plantonista(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'plantonista_id');
    }
}
