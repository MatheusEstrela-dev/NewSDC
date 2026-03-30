<?php

declare(strict_types=1);

namespace App\Models\Rat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ocorrência RAT — entidade raiz do módulo de Registro de Atendimento Técnico.
 *
 * Cada ocorrência (BOS) é o ponto de entrada que agrega todos os relatos
 * polimórficos: dados gerais, envolvidos, recursos, vistoria etc.
 *
 * @property int         $id
 * @property string|null $numero_bos          Número do BOS (ex: 2026-00001)
 * @property int|null    $sequencial_ano      Sequencial no ano corrente
 * @property string|null $created_by          ID do usuário criador
 * @property int         $status              0=Rascunho, 1=Finalizado
 * @property \Carbon\Carbon|null $prazo_edicao Prazo limite para edição
 * @property string|null $updated_by
 * @property int|null    $ocorrencia_origem_id BOS pai (autorreferência)
 * @property string|null $historico
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class RatOcorrencia extends Model
{
    use SoftDeletes;

    protected $table = 'rat_ocorrencias';

    protected $fillable = [
        'numero_bos',
        'sequencial_ano',
        'created_by',
        'status',
        'prazo_edicao',
        'updated_by',
        'ocorrencia_origem_id',
        'historico',
    ];

    protected $casts = [
        'status'       => 'integer',
        'prazo_edicao' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    // ─── Relacionamentos ────────────────────────────────────────────────────

    /**
     * Relatos polimórficos vinculados a esta ocorrência.
     * Usa a tabela pivô rat_ocorrencia_relatos.
     */
    public function relatosMorph(): HasMany
    {
        return $this->hasMany(RatOcorrenciaRelato::class, 'ocorrencia_id');
    }

    /**
     * Histórico de eventos desta ocorrência.
     */
    public function historicos(): HasMany
    {
        return $this->hasMany(RatOcorrenciaHistorico::class, 'ocorrencia_id');
    }

    /**
     * BOS pai (ocorrência de origem para casos filhos).
     */
    public function ocorrenciaOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ocorrencia_origem_id');
    }

    /**
     * Ocorrências filhas geradas a partir desta.
     */
    public function ocorrenciasFilhas(): HasMany
    {
        return $this->hasMany(self::class, 'ocorrencia_origem_id');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** Retorna true se a ocorrência está finalizada. */
    public function isFinalizada(): bool
    {
        return $this->status === 1;
    }

    /** Retorna o rótulo legível do status. */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 1 ? 'Finalizado' : 'Rascunho';
    }
}
