<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Application\DTOs\MovimentacaoListDTO;
use App\Modules\Tdap\Domain\Repositories\MovimentacaoRepositoryInterface;

class ListMovimentacoesUseCase
{
    public function __construct(
        private readonly MovimentacaoRepositoryInterface $movimentacaoRepository
    ) {}

    public function execute(array $filters = [], int $perPage = 15): MovimentacaoListDTO
    {
        $movimentacoes = $this->movimentacaoRepository->list($filters, $perPage);
        $statistics = $this->movimentacaoRepository->getStatistics($filters);

        return new MovimentacaoListDTO(
            movimentacoes: $movimentacoes,
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
        $paginator = $this->movimentacaoRepository->list($filters, $perPage);
        $statistics = $this->movimentacaoRepository->getStatistics($filters);

        return [
            'data' => collect($paginator->items())
                ->map(function ($movimentacao) {
                    return [
                        'id' => $movimentacao->id,
                        'product_id' => $movimentacao->product_id,
                        'lote_id' => $movimentacao->lote_id,
                        'tipo' => $movimentacao->tipo?->value,
                        'quantidade' => $movimentacao->quantidade,
                        'responsavel_id' => $movimentacao->responsavel_id,
                        'destino' => $movimentacao->destino,
                        'observacoes' => $movimentacao->observacoes,
                        'data_movimentacao' => $movimentacao->data_movimentacao?->toIso8601String(),
                        'created_at' => $movimentacao->created_at?->toIso8601String(),
                        'updated_at' => $movimentacao->updated_at?->toIso8601String(),
                        // Relacionamentos se estiverem carregados
                        'product' => $movimentacao->relationLoaded('product') ? [
                            'id' => $movimentacao->product->id,
                            'codigo' => $movimentacao->product->codigo,
                            'nome' => $movimentacao->product->nome,
                        ] : null,
                        'lote' => $movimentacao->relationLoaded('lote') ? [
                            'id' => $movimentacao->lote->id,
                            'numero_lote' => $movimentacao->lote->numero_lote,
                        ] : null,
                        'responsavel' => $movimentacao->relationLoaded('responsavel') ? [
                            'id' => $movimentacao->responsavel->id,
                            'name' => $movimentacao->responsavel->name,
                        ] : null,
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
