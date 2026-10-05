<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Models;

use App\Models\User;
use App\Modules\Acessos\Enums\EstadoSincronizacaoAd;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rodada de sincronizacao dos cadastros com o AD (agendada ou manual).
 * codigo_erro fica em string: alem dos codigos do diretorio a rodada usa
 * concorrente e resultado_suspeito.
 */
class SincronizacaoAd extends Model
{
    use HasUuids;

    protected $table = 'acessos_sincronizacoes_ad';

    protected $fillable = [
        'disparada_por_id', 'simulacao', 'estado', 'codigo_erro', 'totais', 'iniciada_em', 'concluida_em',
    ];

    protected $attributes = [
        'estado' => 'solicitada',
        'simulacao' => false,
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoSincronizacaoAd::class,
            'simulacao' => 'boolean',
            'totais' => 'array',
            'iniciada_em' => 'datetime',
            'concluida_em' => 'datetime',
        ];
    }

    public function disparadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disparada_por_id');
    }

    public function divergencias(): HasMany
    {
        return $this->hasMany(DivergenciaAd::class, 'sincronizacao_id');
    }
}
