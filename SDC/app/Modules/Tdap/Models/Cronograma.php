<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\Municipio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int             $id
 * @property string          $numero
 * @property ?string         $empenho
 * @property int             $ata_id
 * @property int             $lote_id
 * @property int             $municipio_id
 * @property int             $prestador_id
 * @property ?string         $cnpj
 * @property float           $consumo_diario
 * @property int             $dias
 * @property float           $fator
 * @property bool            $usar_fator_manual
 * @property \Carbon\Carbon  $dt_inicio
 * @property \Carbon\Carbon  $dt_final
 * @property ?\Carbon\Carbon $dt_inicio_prorrogacao
 * @property ?\Carbon\Carbon $dt_final_prorrogacao
 * @property ?string         $justificativa
 * @property ?string         $nota_empenho
 * @property ?int            $ponto_captacao_id
 * @property ?int            $user_id
 * @property bool            $ativo
 * @property ?\Carbon\Carbon $ativado_em
 * @property ?\Carbon\Carbon $encerrado_em
 * @property ?string         $observacao
 * @property ?array          $stored_caminhoes
 * @property ?array          $stored_pmda_ponto
 * @property ?array          $stored_municipio
 * @property ?array          $stored_prestador
 * @property ?string         $processo_tdap_id
 */
class Cronograma extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_cronogramas';

    protected $fillable = [
        'numero',
        'empenho',
        'ata_id',
        'lote_id',
        'municipio_id',
        'prestador_id',
        'cnpj',
        'consumo_diario',
        'dias',
        'fator',
        'usar_fator_manual',
        'dt_inicio',
        'dt_final',
        'dt_inicio_prorrogacao',
        'dt_final_prorrogacao',
        'justificativa',
        'nota_empenho',
        'ponto_captacao_id',
        'user_id',
        'ativo',
        'ativado_em',
        'encerrado_em',
        'observacao',
        'stored_caminhoes',
        'stored_pmda_ponto',
        'stored_municipio',
        'stored_prestador',
        'processo_tdap_id',
    ];

    protected $casts = [
        'ata_id'                 => 'integer',
        'lote_id'                => 'integer',
        'municipio_id'           => 'integer',
        'prestador_id'           => 'integer',
        'consumo_diario'         => 'decimal:2',
        'dias'                   => 'integer',
        'fator'                  => 'decimal:2',
        'usar_fator_manual'      => 'boolean',
        'dt_inicio'              => 'date',
        'dt_final'               => 'date',
        'dt_inicio_prorrogacao'  => 'date',
        'dt_final_prorrogacao'   => 'date',
        'ativo'                  => 'boolean',
        'ativado_em'             => 'datetime',
        'encerrado_em'           => 'datetime',
        'stored_caminhoes'       => 'array',
        'stored_pmda_ponto'      => 'array',
        'stored_municipio'       => 'array',
        'stored_prestador'       => 'array',
    ];

    public function ata(): BelongsTo
    {
        return $this->belongsTo(Ata::class, 'ata_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'prestador_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pontoCaptacao(): BelongsTo
    {
        return $this->belongsTo(PontoCaptacao::class, 'ponto_captacao_id');
    }

    public function processoTdap(): BelongsTo
    {
        return $this->belongsTo(ProcessoTdap::class, 'processo_tdap_id');
    }

    public function caminhoes(): HasMany
    {
        return $this->hasMany(CronoCaminhao::class, 'cronograma_id')->orderBy('ordem');
    }

    public function viagens(): HasMany
    {
        // helper indireto: todas as viagens deste cronograma
        return $this->hasMany(CronoViagem::class, 'crono_caminhao_id')
            ->whereIn('crono_caminhao_id', fn ($q) => $q->select('id')->from('tdap_crono_caminhoes')->where('cronograma_id', $this->id));
    }

    /* Computed */

    public function getEstadoAttribute(): string
    {
        if ($this->encerrado_em) return 'encerrado';
        if ($this->ativo)        return 'ativo';
        return 'rascunho';
    }

    public function getDtInicioEfetivaAttribute(): ?\Carbon\Carbon
    {
        return $this->dt_inicio_prorrogacao ?? $this->dt_inicio;
    }

    public function getDtFinalEfetivaAttribute(): ?\Carbon\Carbon
    {
        return $this->dt_final_prorrogacao ?? $this->dt_final;
    }

    /**
     * Volume contratado em m3. Alinhado ao legado: o "fator" e o proprio
     * volume contratado (auto = consumo_diario_litros * dias / 1000).
     */
    public function getVolumeContratadoAttribute(): float
    {
        return (float) $this->fator;
    }

    /**
     * Fator calculado a partir do consumo (litros) e dias, em m3.
     */
    public function getFatorCalculadoAttribute(): float
    {
        return round(((float) $this->consumo_diario * (int) $this->dias) / 1000, 2);
    }

    /* Scopes */

    public function scopeRascunho(Builder $query): Builder
    {
        return $query->where('ativo', false)->whereNull('encerrado_em');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true)->whereNull('encerrado_em');
    }

    public function scopeEncerrado(Builder $query): Builder
    {
        return $query->whereNotNull('encerrado_em');
    }

    public function scopeDoPrestador(Builder $query, int $prestadorId): Builder
    {
        return $query->where('prestador_id', $prestadorId);
    }

    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (! $termo) return $query;
        $like = '%'.mb_strtoupper($termo).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('UPPER(numero) LIKE ?', [$like])
              ->orWhereRaw('UPPER(empenho) LIKE ?', [$like])
              ->orWhereRaw('UPPER(nota_empenho) LIKE ?', [$like]);
        });
    }
}
