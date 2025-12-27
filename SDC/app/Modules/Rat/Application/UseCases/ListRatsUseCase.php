<?php

namespace App\Modules\Rat\Application\UseCases;

use App\Modules\Rat\Application\DTOs\RatListDTO;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListRatsUseCase
{
    public function __construct(
        private readonly RatRepositoryInterface $repository
    ) {
    }

    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findAll($filters, $perPage);
    }

    /**
     * Executa e retorna dados serializados para Inertia
     * (Paginator convertido em array compatível com JSON)
     */
    public function executeAsDTO(array $filters = [], int $perPage = 15): array
    {
        $paginator = $this->repository->findAll($filters, $perPage);

        return [
            'data' => collect($paginator->items())
                ->map(function ($rat) {
                    // Verificar se é um modelo Eloquent ou stdClass (mock)
                    $isEloquent = $rat instanceof \Illuminate\Database\Eloquent\Model;

                    return [
                        'id' => $rat->id ?? null,
                        'protocolo' => $rat->protocolo ?? null,
                        'tipo_demanda' => $rat->tipo_demanda ?? null,
                        'municipio' => $rat->municipio ?? ($rat->local['municipio'] ?? null),
                        'status' => $rat->status ?? null,
                        'descricao' => $rat->descricao ?? null,
                        'created_at' => isset($rat->created_at) ? (is_string($rat->created_at) ? $rat->created_at : $rat->created_at->toIso8601String()) : null,
                        'updated_at' => isset($rat->updated_at) ? (is_string($rat->updated_at) ? $rat->updated_at : $rat->updated_at->toIso8601String()) : null,
                        // Adicionar mais campos conforme necessário
                        'solicitante' => ($isEloquent && $rat->relationLoaded('solicitante')) ? [
                            'id' => $rat->solicitante->id,
                            'name' => $rat->solicitante->name,
                            'email' => $rat->solicitante->email,
                        ] : null,
                        'tecnico_responsavel' => ($isEloquent && $rat->relationLoaded('tecnicoResponsavel')) ? [
                            'id' => $rat->tecnicoResponsavel->id,
                            'name' => $rat->tecnicoResponsavel->name,
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
        ];
    }
}

