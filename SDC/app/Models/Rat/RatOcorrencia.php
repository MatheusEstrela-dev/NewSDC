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
        'anexos',
    ];

    protected $casts = [
        'status'       => 'integer',
        'prazo_edicao' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
        'anexos'       => 'array',
        'historico'    => 'array',
    ];

    protected $appends = [
        'protocolo',
    ];

    // ─── Relacionamentos ────────────────────────────────────────────────────

    /**
     * Relatos polimórficos vinculados a esta ocorrência.
     * Usa a tabela pivô rat_ocorrencia_relatos.
     */
    public function relatos_ocorrencia(): HasMany
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

    /** Usuário que criou o RAT. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /** Usuário que realizou a última atualização. */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /** Órgão responsável por este RAT. */
    public function orgaoEmissor(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Compdec\Models\Orgao::class, 'orgao_emissor_id');
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

    public function getProtocoloAttribute(): string
    {
        if (!$this->sequencial_ano) return '';
        $ano = $this->created_at ? $this->created_at->year : date('Y');
        return "RAT-{$ano}-" . str_pad((string)$this->sequencial_ano, 5, '0', STR_PAD_LEFT);
    }

    public function getDadosGeraisAttribute(): array
    {
        $dados = $this->relatos_ocorrencia()->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais::class)->first()?->conteudo;
        return $dados ? $dados->toArray() : [];
    }

    public function getLocalAttribute(): array { return $this->getDadosGeraisAttribute(); }
    public function getEnderecoAttribute(): array { return $this->getDadosGeraisAttribute(); }
    public function getComunicacaoAttribute(): array { return $this->getDadosGeraisAttribute(); }

    public function getRecursosAttribute(): array
    {
        $relatos = $this->relatos_ocorrencia()
            ->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoRecurso::class)
            ->get();
            
        return $relatos->map(function($r) {
            $conteudo = $r->conteudo;
            if ($conteudo) {
                // Carrega agentes se existirem no modelo polimórfico
                $conteudo->load('agentes');
                return $conteudo->toArray();
            }
            return null;
        })->filter()->values()->toArray();
    }

    public function getEnvolvidosAttribute(): array
    {
        $relatos = $this->relatos_ocorrencia()->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos::class)->get();
        return $relatos->map(fn($r) => $r->conteudo)->filter()->values()->toArray();
    }


    public function getVistoriaAttribute(): array
    {
        $vistoria = $this->relatos_ocorrencia()->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoVistoria::class)->first()?->conteudo;
        return $vistoria ? $vistoria->toArray() : [];
    }

    public function getTemVistoriaAttribute(): bool
    {
        return !empty($this->getVistoriaAttribute());
    }
}
