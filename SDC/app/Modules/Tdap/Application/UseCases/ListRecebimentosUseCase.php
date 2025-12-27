<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Application\DTOs\RecebimentoListDTO;
use App\Modules\Tdap\Domain\Repositories\RecebimentoRepositoryInterface;

class ListRecebimentosUseCase
{
    public function __construct(
        private readonly RecebimentoRepositoryInterface $recebimentoRepository
    ) {}

    public function execute(array $filters = [], int $perPage = 15): RecebimentoListDTO
    {
        $recebimentos = $this->recebimentoRepository->list($filters, $perPage);
        $statistics = $this->recebimentoRepository->getStatistics($filters);

        return new RecebimentoListDTO(
            recebimentos: $recebimentos,
            filters: $filters,
            statistics: $statistics,
        );
    }

    /**
     * Executa e retorna dados serializados para Inertia
     * (Paginator convertido em array compatível com JSON)
     */
    public function executeAsDTO(array $filters = [], int $perPage = 15): array
    {
        $paginator = $this->recebimentoRepository->list($filters, $perPage);
        $statistics = $this->recebimentoRepository->getStatistics($filters);

        return [
            'data' => collect($paginator->items())
                ->map(function ($recebimento) {
                    return [
                        'id' => $recebimento->id,
                        'numero_recebimento' => $recebimento->numero_recebimento,
                        'ordem_compra_id' => $recebimento->ordem_compra_id,
                        'nota_fiscal' => $recebimento->nota_fiscal,
                        'placa_veiculo' => $recebimento->placa_veiculo,
                        'transportadora' => $recebimento->transportadora,
                        'motorista_nome' => $recebimento->motorista_nome,
                        'motorista_documento' => $recebimento->motorista_documento,
                        'doca_descarga' => $recebimento->doca_descarga,
                        'data_chegada' => $recebimento->data_chegada?->toIso8601String(),
                        'data_inicio_conferencia' => $recebimento->data_inicio_conferencia?->toIso8601String(),
                        'data_fim_conferencia' => $recebimento->data_fim_conferencia?->toIso8601String(),
                        'conferido_por' => $recebimento->conferido_por,
                        'aprovado_por' => $recebimento->aprovado_por,
                        'status' => $recebimento->status?->value,
                        'observacoes' => $recebimento->observacoes,
                        'created_at' => $recebimento->created_at?->toIso8601String(),
                        'updated_at' => $recebimento->updated_at?->toIso8601String(),
                    ];
                })
                ->toArray(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => $filters,
            'statistics' => $statistics,
        ];
    }
}
