<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\DTOs;

use App\Modules\Demandas\Domain\Entities\Task;
use Illuminate\Support\Carbon;

/**
 * DTO: Task para Lista
 *
 * Representa dados de uma task para exibição em listagens
 */
readonly class TaskListDTO
{
    public function __construct(
        public int $id,
        public string $protocolo,
        public string $tipo,
        public string $tipoLabel,
        public string $titulo,
        public string $status,
        public string $statusLabel,
        public string $statusColor,
        public int $prioridade,
        public string $prioridadeLabel,
        public string $prioridadeColor,
        public ?string $categoria,
        public ?string $solicitanteNome,
        public ?string $solicitanteEmail,
        public ?string $atribuidoParaNome,
        public ?string $atribuidoParaEmail,
        public bool $atrasada,
        public bool $slaViolado,
        public Carbon $criadoEm,
        public ?Carbon $resolvidoEm,
    ) {
    }

    /**
     * Criar DTO a partir da entidade Task
     */
    public static function fromEntity(Task $task): self
    {
        return new self(
            id: $task->id,
            protocolo: $task->protocolo,
            tipo: $task->tipo->value,
            tipoLabel: $task->tipo->getLabel(),
            titulo: $task->titulo,
            status: $task->status->value,
            statusLabel: $task->status->getLabel(),
            statusColor: $task->status->getColorClass(),
            prioridade: $task->prioridade->value,
            prioridadeLabel: $task->prioridade->getLabel(),
            prioridadeColor: $task->prioridade->getColorClass(),
            categoria: $task->categoria,
            solicitanteNome: $task->solicitante?->name,
            solicitanteEmail: $task->solicitante?->email,
            atribuidoParaNome: $task->atribuidoPara?->name,
            atribuidoParaEmail: $task->atribuidoPara?->email,
            atrasada: $task->isAtrasada(),
            slaViolado: $task->sla_resolucao_violado,
            criadoEm: $task->created_at,
            resolvidoEm: $task->resolvido_em,
        );
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'protocolo' => $this->protocolo,
            'tipo' => $this->tipo,
            'tipo_label' => $this->tipoLabel,
            'titulo' => $this->titulo,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'status_color' => $this->statusColor,
            'prioridade' => $this->prioridade,
            'prioridade_label' => $this->prioridadeLabel,
            'prioridade_color' => $this->prioridadeColor,
            'categoria' => $this->categoria,
            'solicitante' => [
                'nome' => $this->solicitanteNome,
                'email' => $this->solicitanteEmail,
            ],
            'atribuido_para' => [
                'nome' => $this->atribuidoParaNome,
                'email' => $this->atribuidoParaEmail,
            ],
            'atrasada' => $this->atrasada,
            'sla_violado' => $this->slaViolado,
            'criado_em' => $this->criadoEm->toISOString(),
            'criado_em_diff' => $this->criadoEm->diffForHumans(),
            'resolvido_em' => $this->resolvidoEm?->toISOString(),
        ];
    }
}
