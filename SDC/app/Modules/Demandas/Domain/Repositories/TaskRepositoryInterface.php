<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Repositories;

use App\Modules\Demandas\Domain\Entities\Task;
use App\Modules\Demandas\Domain\ValueObjects\TaskStatus;
use App\Modules\Demandas\Domain\ValueObjects\TipoTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repository Interface: Task
 *
 * Contrato para operações de persistência de Tasks
 * Implementação em Infrastructure Layer
 */
interface TaskRepositoryInterface
{
    /**
     * Buscar task por ID
     */
    public function find(int $id): ?Task;

    /**
     * Buscar task por protocolo
     */
    public function findByProtocolo(string $protocolo): ?Task;

    /**
     * Listar tasks com filtros e paginação
     *
     * @param array{
     *     protocolo?: string,
     *     status?: string|array,
     *     tipo?: string,
     *     prioridade?: int|array,
     *     categoria?: string,
     *     solicitante_id?: int,
     *     atribuido_para_id?: int,
     *     data_inicio?: string,
     *     data_fim?: string,
     *     atrasadas?: bool,
     *     search?: string
     * } $filters
     */
    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Criar nova task
     */
    public function create(array $data): Task;

    /**
     * Atualizar task
     */
    public function update(int $id, array $data): Task;

    /**
     * Deletar task (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Obter estatísticas de tasks
     *
     * @param array $filters
     * @return array{
     *     total: int,
     *     hoje: int,
     *     esteMes: int,
     *     esteAno: int,
     *     porStatus: array,
     *     porPrioridade: array,
     *     porTipo: array,
     *     atrasadas: int,
     *     mediaTempoResolucao: float|null
     * }
     */
    public function getStatistics(array $filters = []): array;

    /**
     * Contar tasks por status
     */
    public function countByStatus(TaskStatus $status): int;

    /**
     * Contar tasks atrasadas
     */
    public function countAtrasadas(): int;

    /**
     * Listar tasks abertas por tipo
     */
    public function findAbertasPorTipo(TipoTask $tipo, int $limit = 10): array;

    /**
     * Listar tasks atribuídas a um usuário
     */
    public function findAtribuidasPara(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Listar tasks solicitadas por um usuário
     */
    public function findSolicitadasPor(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Obter tempo médio de resolução por tipo
     */
    public function getMediaTempoResolucaoPorTipo(TipoTask $tipo): ?float;
}
