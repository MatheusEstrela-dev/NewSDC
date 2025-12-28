<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Repositories;

use App\Modules\AjudaHumanitaria\Domain\Entities\Beneficiario;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\StatusBeneficiario;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\SituacaoVulnerabilidade;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BeneficiarioRepositoryInterface
{
    /**
     * Lista beneficiários com filtros e paginação
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Encontra beneficiário por ID
     */
    public function findById(int $id): ?Beneficiario;

    /**
     * Encontra beneficiário por CPF
     */
    public function findByCpf(string $cpf): ?Beneficiario;

    /**
     * Cria novo beneficiário
     */
    public function create(array $data): Beneficiario;

    /**
     * Atualiza beneficiário
     */
    public function update(int $id, array $data): Beneficiario;

    /**
     * Remove beneficiário (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Lista beneficiários por status
     */
    public function findByStatus(StatusBeneficiario $status): Collection;

    /**
     * Lista beneficiários por situação de vulnerabilidade
     */
    public function findBySituacao(SituacaoVulnerabilidade $situacao): Collection;

    /**
     * Lista beneficiários em abrigos
     */
    public function findEmAbrigo(): Collection;

    /**
     * Lista beneficiários fora de abrigos
     */
    public function findForaDeAbrigo(): Collection;

    /**
     * Lista beneficiários por município
     */
    public function findByMunicipio(int $municipioId): Collection;

    /**
     * Obtém estatísticas gerais de beneficiários
     */
    public function getStatistics(): array;

    /**
     * Conta total de beneficiários ativos
     */
    public function countAtivos(): int;

    /**
     * Conta total de pessoas assistidas (incluindo membros da família)
     */
    public function countTotalPessoasAssistidas(): int;
}
