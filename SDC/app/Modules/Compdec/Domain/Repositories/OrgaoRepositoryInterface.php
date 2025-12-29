<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Domain\Repositories;

use App\Modules\Compdec\Domain\Entities\Orgao;
use App\Modules\Compdec\Domain\ValueObjects\TipoOrgao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrgaoRepositoryInterface
{
    /**
     * Buscar órgão por ID
     */
    public function find(int $id): ?Orgao;

    /**
     * Buscar órgão por código
     */
    public function findByCodigo(string $codigo): ?Orgao;

    /**
     * Buscar COMPDEC por município
     */
    public function findByMunicipio(int $municipioId): ?Orgao;

    /**
     * Listar todos os órgãos com filtros e paginação
     */
    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Criar novo órgão
     */
    public function create(array $data): Orgao;

    /**
     * Atualizar órgão
     */
    public function update(int $id, array $data): Orgao;

    /**
     * Deletar órgão (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Buscar órgãos por tipo
     */
    public function findByTipo(TipoOrgao $tipo): Collection;

    /**
     * Buscar subordinados de um órgão
     */
    public function findSubordinados(int $orgaoId): Collection;

    /**
     * Buscar órgão superior
     */
    public function findOrgaoSuperior(int $orgaoId): ?Orgao;

    /**
     * Obter hierarquia completa (CEDEC → REDEC → COMPDEC)
     */
    public function getHierarquiaCompleta(int $orgaoId): array;

    /**
     * Contar órgãos por estado
     */
    public function countByEstado(string $uf): array;

    /**
     * Buscar órgãos órfãos (sem superior mas que deveriam ter)
     */
    public function findOrfaos(): Collection;

    /**
     * Obter estatísticas gerais
     */
    public function getStatistics(): array;
}
