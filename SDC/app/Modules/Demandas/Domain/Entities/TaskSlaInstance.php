<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Instância de SLA - Tempo correndo em um ticket
 */
class TaskSlaInstance extends Model
{
    protected $table = 'task_sla_instances';

    protected $fillable = [
        'task_id',
        'sla_definition_id',
        'primeira_resposta_inicio',
        'primeira_resposta_prazo',
        'primeira_resposta_atingido',
        'primeira_resposta_tempo_util_decorrido',
        'primeira_resposta_violado',
        'primeira_resposta_percentual',
        'resolucao_inicio',
        'resolucao_prazo',
        'resolucao_atingido',
        'resolucao_tempo_util_decorrido',
        'resolucao_violado',
        'resolucao_percentual',
        'pausado_em',
        'tempo_pausado_total',
        'ultima_atualizacao',
    ];

    protected $casts = [
        'primeira_resposta_inicio' => 'datetime',
        'primeira_resposta_prazo' => 'datetime',
        'primeira_resposta_atingido' => 'datetime',
        'primeira_resposta_tempo_util_decorrido' => 'integer',
        'primeira_resposta_violado' => 'boolean',
        'primeira_resposta_percentual' => 'decimal:2',
        'resolucao_inicio' => 'datetime',
        'resolucao_prazo' => 'datetime',
        'resolucao_atingido' => 'datetime',
        'resolucao_tempo_util_decorrido' => 'integer',
        'resolucao_violado' => 'boolean',
        'resolucao_percentual' => 'decimal:2',
        'pausado_em' => 'datetime',
        'tempo_pausado_total' => 'integer',
        'ultima_atualizacao' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function slaDefinition(): BelongsTo
    {
        return $this->belongsTo(TaskSlaDefinition::class, 'sla_definition_id');
    }

    /**
     * Verifica se está pausado
     */
    public function isPausado(): bool
    {
        return ! is_null($this->pausado_em);
    }

    /**
     * Pausar SLA (quando aguardando terceiros)
     */
    public function pausar(): self
    {
        if (! $this->isPausado()) {
            $this->pausado_em = now();
            $this->save();
        }

        return $this;
    }

    /**
     * Retomar SLA
     */
    public function retomar(): self
    {
        if ($this->isPausado()) {
            $minutosNaPausa = now()->diffInMinutes($this->pausado_em);
            $this->tempo_pausado_total += $minutosNaPausa;
            $this->pausado_em = null;
            $this->save();
        }

        return $this;
    }
}
