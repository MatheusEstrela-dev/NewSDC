<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use App\Modules\Plantao\Enums\StatusReserva;
use App\Modules\Plantao\Enums\StatusViatura;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Viatura extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'plantao_viaturas';

    protected $fillable = [
        'prefixo',
        'placa',
        'marca',
        'modelo',
        'localizacao',
        'exclusiva_sobreaviso',
        'status',
        'qr_token',
        'hodometro_atual',
        'nivel_combustivel',
        'ultimo_condutor_id',
        'ultimo_condutor_nome',
        'observacoes',
        'ativo',
    ];

    protected $casts = [
        'localizacao' => LocalizacaoViatura::class,
        'status' => StatusViatura::class,
        'nivel_combustivel' => NivelCombustivel::class,
        'exclusiva_sobreaviso' => 'boolean',
        'ativo' => 'boolean',
        'hodometro_atual' => 'integer',
    ];

    protected static function newFactory(): \Database\Factories\Plantao\ViaturaFactory
    {
        return \Database\Factories\Plantao\ViaturaFactory::new();
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(ViaturaMovimentacao::class, 'viatura_id');
    }

    /**
     * A saida ainda nao retornada. Regra de negocio garante no maximo uma.
     */
    public function movimentacaoAberta(): HasOne
    {
        return $this->hasOne(ViaturaMovimentacao::class, 'viatura_id')
            ->where('status', StatusMovimentacao::EM_TRANSITO->value);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ViaturaSnapshot::class, 'viatura_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(ViaturaReserva::class, 'viatura_id');
    }

    /**
     * A reserva cuja chave ja foi retirada. Espelha movimentacaoAberta(): a
     * regra de negocio garante no maximo uma, porque o check-in so ocorre com a
     * viatura sem movimentacao aberta.
     */
    public function reservaEmUso(): HasOne
    {
        return $this->hasOne(ViaturaReserva::class, 'viatura_id')
            ->where('status', StatusReserva::EM_USO->value);
    }

    /**
     * A proxima reserva agendada, se houver. E o que tira a viatura de
     * "disponivel" na tela da frota: uma vez reservada, ela nao pode ser
     * oferecida como livre -- nem que a janela seja na semana que vem, porque
     * quem sair com ela hoje pode atrasar e furar a reserva marcada.
     *
     * A ordenacao importa: com varias reservas futuras, a que interessa mostrar
     * e a mais proxima.
     */
    public function reservaAgendada(): HasOne
    {
        return $this->hasOne(ViaturaReserva::class, 'viatura_id')
            ->where('status', StatusReserva::AGENDADA->value)
            ->orderBy('inicio_previsto');
    }

    public function ultimoCondutor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'ultimo_condutor_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
