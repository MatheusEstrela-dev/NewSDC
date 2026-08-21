<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
    use TrilhaNoProtocoloPai;

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
        'arquivado_em',
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
        'arquivado_em'           => 'datetime',
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

    public function comprovantes(): HasMany
    {
        return $this->hasMany(CronogramaComprovante::class, 'cronograma_id')->latest();
    }

    /**
     * Todas as viagens do cronograma, atravessando os caminhoes alocados.
     *
     * Era um `hasMany(CronoViagem, 'crono_caminhao_id')`, que compara
     * `crono_caminhao_id` com o ID DO CRONOGRAMA -- chaves de tabelas
     * diferentes. O `whereIn` extra estreitava o resultado errado em vez de
     * corrigi-lo: o cronograma 7 devolvia as viagens do crono_caminhao 7.
     */
    public function viagens(): HasManyThrough
    {
        return $this->hasManyThrough(
            CronoViagem::class,
            CronoCaminhao::class,
            'cronograma_id',      // FK em tdap_crono_caminhoes -> este cronograma
            'crono_caminhao_id',  // FK em tdap_crono_viagens    -> crono_caminhao
            'id',
            'id',
        );
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
     * Volume contratado em m3: a soma da agua prevista dos caminhoes alocados.
     *
     * NAO e o `fator`. O fator vale no maximo 0,60 em toda a base -- ele e o
     * resultado de (consumo_diario x dias) / 1000, uma grandeza POR PESSOA --
     * enquanto os caminhoes do mesmo cronograma somam de 90 a 3.653 m3. Enquanto
     * este accessor devolvia `fator`, o card "Volume ativo" do dashboard e a
     * coluna "Volume Contratado (m3)" do CSV anunciavam 0,60 m3 para operacoes
     * de centenas de metros cubicos.
     *
     * Ordem de preferencia (evita N+1 na listagem): `withSum` -> relacao ja
     * carregada -> agregacao propria. A ultima existe so para o accessor nunca
     * mentir por falta de eager loading.
     */
    public function getVolumeContratadoAttribute(): float
    {
        return $this->somaDosCaminhoes('agua_prevista');
    }

    /** Volume efetivamente entregue em m3 (viagens validadas x capacidade). */
    public function getVolumeEntregueAttribute(): float
    {
        return $this->somaDosCaminhoes('agua_entregue');
    }

    /** Percentual entregue sobre o contratado; 0 quando nao ha nada previsto. */
    public function getPercentualEntregueAttribute(): float
    {
        $previsto = $this->volume_contratado;

        return $previsto > 0 ? round(($this->volume_entregue / $previsto) * 100, 2) : 0.0;
    }

    private function somaDosCaminhoes(string $coluna): float
    {
        $comWithSum = $this->getAttributes()['caminhoes_sum_'.$coluna] ?? null;
        if ($comWithSum !== null) {
            return round((float) $comWithSum, 2);
        }

        if ($this->relationLoaded('caminhoes')) {
            return round((float) $this->caminhoes->sum(fn (CronoCaminhao $cc) => (float) $cc->{$coluna}), 2);
        }

        return round((float) $this->caminhoes()->sum($coluna), 2);
    }

    /**
     * Fator recalculado a partir de consumo_diario e dias.
     *
     * Espelha exatamente a conta do CronogramaDTO, e serve para detectar linha
     * dessincronizada: 35 cronogramas do acervo legado tem `fator` 0,00 com
     * `usar_fator_manual = false`, ou seja, nunca passaram pela conta.
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

    public function scopeArquivado(Builder $query): Builder
    {
        return $query->whereNotNull('arquivado_em');
    }

    public function scopeNaoArquivado(Builder $query): Builder
    {
        return $query->whereNull('arquivado_em');
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

    // ─── Trilha de acoes no protocolo pai ───────────────────────────────────

    public function protocoloDaTrilhaClasse(): string
    {
        return ProcessoTdap::class;
    }

    /**
     * Cronograma solto (ainda sem processo) nao tem a quem reportar.
     */
    public function protocoloDaTrilhaChave(): int|string|null
    {
        return $this->processo_tdap_id;
    }

    /**
     * Editado, e nao Relacionado: mudanca de data no cronograma e o que o dono do
     * processo mais precisa acompanhar, e Relacionado so dispara na criacao -- as
     * remarcacoes posteriores passariam em silencio.
     */
    public function acaoNaTrilhaDoProtocolo(): AcaoTrilha
    {
        return AcaoTrilha::Editado;
    }
}
