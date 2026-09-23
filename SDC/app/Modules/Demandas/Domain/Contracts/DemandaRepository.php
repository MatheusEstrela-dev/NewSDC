<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Contracts;

use App\Modules\Demandas\Models\Demanda;
use Illuminate\Pagination\LengthAwarePaginator;

interface DemandaRepository
{
    /**
     * Busca uma demanda pelo ID com seus relacionamentos carregados.
     */
    public function findById(int $id): ?Demanda;

    /**
     * Busca uma demanda pelo Protocolo.
     */
    public function findByProtocolo(string $protocolo): ?Demanda;

    /**
     * Lista demandas com filtros e paginação.
     */
    public function paginate(array $filters, int $perPage, int $viewerId, bool $manage, ?int $page = null): LengthAwarePaginator;

    /**
     * Retorna as estatísticas consolidadas (Total, Abertas, Em Andamento, Concluídas).
     */
    public function getStatistics(int $viewerId, bool $manage): array;

    /**
     * Salva (cria ou atualiza) a demanda no banco.
     */
    public function save(Demanda $demanda): Demanda;

    /**
     * Remove a demanda (soft delete).
     */
    public function delete(Demanda $demanda): bool;
}

