<?php

declare(strict_types=1);

namespace App\Modules\Rat\Application\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatStatisticsService;
use App\Modules\Rat\Services\RatWriteService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Orquestrador da camada de aplicação do módulo RAT.
 *
 * Responsabilidade única: coordena casos de uso sem lógica de negócio própria.
 * Inversão de Dependência: depende de interfaces e services especializados.
 *
 * 5 métodos públicos: createNew · getIndexData · findById · delete · export
 * Finalização  → RatWriteService (chamado diretamente pelo RatFinalizeController).
 * Update/draft → RatWriteService (chamado diretamente pelo RatWriteController).
 */
class RatService
{
    public function __construct(
        private readonly RatRepositoryInterface $repository,
        private readonly RatStatisticsService   $statisticsService,
        private readonly RatExportService       $exportService,
        private readonly RatWriteService        $writeService,
    ) {}

    /**
     * Cria um novo RAT em branco (rascunho com protocolo automático).
     */
    public function createNew(): object
    {
        return $this->writeService->create();
    }

    /**
     * Cria um novo RAT com dados do formulário (rascunho com protocolo automático).
     */
    public function createWithData(array $data): object
    {
        return $this->writeService->createWithData($data);
    }

    /**
     * Finaliza um RAT existente.
     */
    public function finalize(string $id): object
    {
        return $this->writeService->finalize($id);
    }

    /**
     * Dados completos para a página de índice: lista + estatísticas + opções de filtro.
     */
    public function getIndexData(RatFilterDTO $filters): array
    {
        return [
            'rats'           => $this->repository->paginate($filters),
            'statistics'     => $this->statisticsService->getStatistics()->toArray(),
            'municipalities' => $this->repository->getMunicipalities(),
        ];
    }

    /**
     * Busca um RAT por UUID, retorna null se não encontrado.
     */
    public function findById(string $id): ?object
    {
        return $this->repository->findById($id);
    }

    /**
     * Remove o RAT permanentemente.
     */
    public function delete(string $id): void
    {
        $this->repository->delete($id);
    }

    /** Gera resposta de download CSV com os RATs filtrados. */
    public function export(RatFilterDTO $filters): StreamedResponse
    {
        return $this->exportService->exportToCsv($filters);
    }
}
