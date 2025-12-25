<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\DTOs;

/**
 * DTO: Estatísticas de Tasks
 */
readonly class TaskStatisticsDTO
{
    public function __construct(
        public int $total,
        public int $hoje,
        public int $esteMes,
        public int $esteAno,
        public array $porStatus,
        public array $porPrioridade,
        public array $porTipo,
        public int $atrasadas,
        public ?float $mediaTempoResolucao,
    ) {
    }

    /**
     * Criar a partir de array de dados
     */
    public static function fromArray(array $data): self
    {
        return new self(
            total: $data['total'] ?? 0,
            hoje: $data['hoje'] ?? 0,
            esteMes: $data['esteMes'] ?? 0,
            esteAno: $data['esteAno'] ?? 0,
            porStatus: $data['porStatus'] ?? [],
            porPrioridade: $data['porPrioridade'] ?? [],
            porTipo: $data['porTipo'] ?? [],
            atrasadas: $data['atrasadas'] ?? 0,
            mediaTempoResolucao: $data['mediaTempoResolucao'] ?? null,
        );
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'hoje' => $this->hoje,
            'este_mes' => $this->esteMes,
            'este_ano' => $this->esteAno,
            'por_status' => $this->porStatus,
            'por_prioridade' => $this->porPrioridade,
            'por_tipo' => $this->porTipo,
            'atrasadas' => $this->atrasadas,
            'media_tempo_resolucao' => $this->mediaTempoResolucao,
        ];
    }
}
