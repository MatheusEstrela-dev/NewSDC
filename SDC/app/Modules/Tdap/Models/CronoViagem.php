<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int             $id
 * @property int             $crono_caminhao_id
 * @property \Carbon\Carbon  $data_registro
 * @property ?\Carbon\Carbon $data_aprovacao
 * @property ?string         $obs
 * @property ?string         $obs_aprovacao
 * @property ?int            $validado  NULL=pendente, 1=aprovada, 0=rejeitada
 * @property ?int            $user_validacao_id
 */
class CronoViagem extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_crono_viagens';

    public const STATUS_PENDENTE = null;
    public const STATUS_APROVADA = 1;
    public const STATUS_REJEITADA = 0;

    protected $fillable = [
        'crono_caminhao_id',
        'data_registro',
        'data_aprovacao',
        'obs',
        'obs_aprovacao',
        'validado',
        'user_validacao_id',
        'confirmado_em',
        'confirmado_por',
        'obs_confirmacao',
    ];

    protected $casts = [
        'crono_caminhao_id' => 'integer',
        'data_registro'     => 'datetime',
        'data_aprovacao'    => 'datetime',
        'validado'          => 'integer',
        'user_validacao_id' => 'integer',
        'confirmado_em'     => 'datetime',
        'confirmado_por'    => 'integer',
    ];

    public function cronoCaminhao(): BelongsTo
    {
        return $this->belongsTo(CronoCaminhao::class, 'crono_caminhao_id');
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_validacao_id');
    }

    /** Quem, no municipio, atestou que a agua chegou. */
    public function confirmador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    public function getStatusAttribute(): string
    {
        return match (true) {
            $this->validado === 1 => 'aprovada',
            $this->validado === 0 => 'rejeitada',
            default               => 'pendente',
        };
    }

    public function scopePendente(Builder $query): Builder
    {
        return $query->whereNull('validado');
    }

    public function scopeAprovada(Builder $query): Builder
    {
        return $query->where('validado', 1);
    }

    public function scopeRejeitada(Builder $query): Builder
    {
        return $query->where('validado', 0);
    }

    public function scopeDoCaminhao(Builder $query, int $cronoCaminhaoId): Builder
    {
        return $query->where('crono_caminhao_id', $cronoCaminhaoId);
    }

    /** Ainda sem o aceite do municipio. */
    public function scopeNaoConfirmada(Builder $query): Builder
    {
        return $query->whereNull('confirmado_em');
    }

    public function scopeConfirmada(Builder $query): Builder
    {
        return $query->whereNotNull('confirmado_em');
    }

    /**
     * Viagens do municipio indicado, pelo cronograma a que pertencem.
     *
     * O recorte e feito no BANCO, e nao na tela: e o que impede um COMPDEC de
     * alcancar viagem de outro municipio trocando um id na URL.
     */
    public function scopeDoMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->whereHas(
            'cronoCaminhao.cronograma',
            fn (Builder $c) => $c->where('municipio_id', $municipioId),
        );
    }
}
