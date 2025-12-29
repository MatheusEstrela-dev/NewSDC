<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\Entities;

use App\Modules\Decretacoes\Domain\ValueObjects\StatusProcesso;
use App\Modules\Decretacoes\Domain\ValueObjects\TipoProcesso;
use App\Modules\Decretacoes\Domain\ValueObjects\TipoDecreto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Entity: Processo de Reconhecimento de Desastre
 *
 * Aggregate Root do módulo Decretações
 */
class Processo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'processos';

    protected $fillable = [
        'data_entrada',
        'data_ocorrencia_desastre',
        'processo',
        'tipo_decreto',
        'analista',
        'n_protocolo_fide',
        'decreto_municipal',
        'tipo_desastre_id',
        'tipo_desastre_nome',
        'data_decreto_municipal',
        'data_publicacao_mg',
        'prazo_vigencia',
        'status',
        'reconhecimento_decreto_n_data',
        'data_publicacao_diario',
        'portaria_reconhecimento_fed',
        'portaria_diario_oficial',
        'reconhecimento_federal',
        'observacoes',
        'processo_inserido_sei',
        'area_afetada_geom',
        'orgao_responsavel_id',
        'created_by',
    ];

    protected $casts = [
        'data_entrada' => 'date',
        'data_ocorrencia_desastre' => 'date',
        'data_decreto_municipal' => 'date',
        'data_publicacao_mg' => 'date',
        'data_publicacao_diario' => 'date',
        'prazo_vigencia' => 'integer',
        'tipo_desastre_id' => 'integer',
        'processo' => TipoProcesso::class,
        'tipo_decreto' => TipoDecreto::class,
        'status' => StatusProcesso::class,
    ];

    protected $appends = [
        'data_vencimento',
        'dias_restantes',
        'vigente',
        'proximo_vencer',
    ];

    /**
     * Relacionamento: Municípios afetados
     */
    public function municipios(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Municipio::class,
            'processo_municipios',
            'processo_id',
            'municipio_id'
        )->withPivot('n_protocolo_fide')
          ->withTimestamps();
    }

    /**
     * Relacionamento: Danos Humanos
     */
    public function danosHumanos(): HasMany
    {
        return $this->hasMany(ProcessoDanosHumanos::class);
    }

    /**
     * Relacionamento: Danos Materiais
     */
    public function danosMateriais(): HasMany
    {
        return $this->hasMany(ProcessoDanosMateriais::class);
    }

    /**
     * Relacionamento: Prejuízos
     */
    public function prejuizos(): HasMany
    {
        return $this->hasMany(ProcessoPrejuizo::class);
    }

    /**
     * Relacionamento: Anexos/Documentos
     */
    public function anexos(): HasMany
    {
        return $this->hasMany(ProcessoAnexo::class);
    }

    /**
     * Relacionamento: Log de Auditoria
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ProcessoLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Accessor: Data de Vencimento
     * Calcula: data_publicacao_mg + prazo_vigencia dias
     */
    public function getDataVencimentoAttribute(): ?Carbon
    {
        if (!$this->data_publicacao_mg || !$this->prazo_vigencia) {
            return null;
        }

        return $this->data_publicacao_mg->addDays($this->prazo_vigencia);
    }

    /**
     * Accessor: Dias Restantes até vencimento
     */
    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->data_vencimento) {
            return null;
        }

        $hoje = Carbon::today();
        $diff = $hoje->diffInDays($this->data_vencimento, false);

        return $diff < 0 ? 0 : (int) $diff;
    }

    /**
     * Accessor: Está vigente?
     */
    public function getVigenteAttribute(): bool
    {
        if (!$this->data_vencimento) {
            return true; // Se não tem data vencimento, considera vigente
        }

        return $this->data_vencimento->isFuture();
    }

    /**
     * Accessor: Próximo ao vencimento? (< 15 dias)
     */
    public function getProximoVencerAttribute(): bool
    {
        if (!$this->dias_restantes) {
            return false;
        }

        return $this->dias_restantes > 0 && $this->dias_restantes <= 15;
    }

    /**
     * Scope: Filtrar por vigência
     */
    public function scopeVigentes($query)
    {
        return $query->where(function($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
        });
    }

    /**
     * Scope: Filtrar por vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->whereNotNull('data_publicacao_mg')
                     ->whereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) < CURDATE()');
    }

    /**
     * Scope: Próximos ao vencimento
     */
    public function scopeProximosVencer($query)
    {
        return $query->whereNotNull('data_publicacao_mg')
                     ->whereRaw('DATEDIFF(DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY), CURDATE()) BETWEEN 1 AND 15');
    }

    /**
     * Scope: Filtrar por tipo de processo
     */
    public function scopeTipo($query, TipoProcesso|string $tipo)
    {
        $tipoValue = $tipo instanceof TipoProcesso ? $tipo->value : $tipo;
        return $query->where('processo', $tipoValue);
    }

    /**
     * Scope: Filtrar por status
     */
    public function scopeStatus($query, StatusProcesso|string $status)
    {
        $statusValue = $status instanceof StatusProcesso ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    /**
     * Business Logic: Pode ser editado?
     */
    public function podeSerEditado(): bool
    {
        // Não permite edição se já foi reconhecido ou negado
        return !$this->status->isReconhecido() && !$this->status->isNaoReconhecido();
    }

    /**
     * Business Logic: Pode ser excluído?
     */
    public function podeSerExcluido(): bool
    {
        // Só pode excluir se estiver em registro ou aguardando análise
        return in_array($this->status, [
            StatusProcesso::REGISTRO,
            StatusProcesso::AGUARDANDO_ANALISE_ESTADO,
        ]);
    }

    /**
     * Business Logic: Total de pessoas afetadas
     */
    public function getTotalPessoasAfetadas(): int
    {
        return $this->danosHumanos->sum(function ($dano) {
            return $dano->obitos +
                   $dano->feridos +
                   $dano->desabrigados +
                   $dano->desalojados +
                   $dano->desaparecidos +
                   $dano->outros_afetados;
        });
    }

    /**
     * Business Logic: Total de prejuízos
     */
    public function getTotalPrejuizos(): float
    {
        return $this->prejuizos->sum('valor');
    }

    /**
     * Relacionamento: Órgão responsável (COMPDEC)
     */
    public function orgaoResponsavel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Compdec\Domain\Entities\Orgao::class, 'orgao_responsavel_id');
    }
}
