<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Definição de SLA (Regras de Tempo)
 */
class TaskSlaDefinition extends Model
{
    protected $table = 'task_sla_definitions';

    protected $fillable = [
        'nome',
        'descricao',
        'tipo_task',
        'prioridade',
        'categoria',
        'tempo_primeira_resposta',
        'tempo_resolucao',
        'horario_inicio',
        'horario_fim',
        'dias_funcionamento',
        'feriados',
        'alertas',
        'ativo',
    ];

    protected $casts = [
        'tempo_primeira_resposta' => 'integer',
        'tempo_resolucao' => 'integer',
        'dias_funcionamento' => 'array',
        'feriados' => 'array',
        'alertas' => 'array',
        'ativo' => 'boolean',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(TaskSlaInstance::class, 'sla_definition_id');
    }

    /**
     * Verifica se um dia da semana é útil
     *
     * @param int $diaSemana 0=domingo, 1=segunda, ..., 6=sábado
     */
    public function isDiaUtil(int $diaSemana): bool
    {
        return in_array($diaSemana, $this->dias_funcionamento ?? [1, 2, 3, 4, 5], true);
    }

    /**
     * Verifica se uma data é feriado
     */
    public function isFeriado(\Carbon\Carbon $data): bool
    {
        $dataStr = $data->format('Y-m-d');

        return in_array($dataStr, $this->feriados ?? [], true);
    }

    /**
     * Verifica se está dentro do horário de funcionamento
     */
    public function isDentroHorarioFuncionamento(\Carbon\Carbon $dataHora): bool
    {
        $hora = $dataHora->format('H:i:s');

        return $hora >= $this->horario_inicio && $hora <= $this->horario_fim;
    }
}
