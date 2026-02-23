<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Entities\Beneficiario;
use App\Modules\AjudaHumanitaria\Domain\Repositories\BeneficiarioRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\StatusBeneficiario;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\SituacaoVulnerabilidade;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentBeneficiarioRepository implements BeneficiarioRepositoryInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Beneficiario::query();

        // Eager loading padrão
        $query->with(['membrosFamilia', 'abrigoAtual']);

        // Filtro por status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por situação de vulnerabilidade
        if (!empty($filters['situacao_vulnerabilidade'])) {
            $query->where('situacao_vulnerabilidade', $filters['situacao_vulnerabilidade']);
        }

        // Filtro por tipo de cadastro
        if (!empty($filters['tipo_cadastro'])) {
            $query->where('tipo_cadastro', $filters['tipo_cadastro']);
        }

        // Filtro por município
        if (!empty($filters['municipio_id'])) {
            $query->where('municipio_id', $filters['municipio_id']);
        }

        // Filtro por abrigo
        if (isset($filters['abrigo_id'])) {
            if ($filters['abrigo_id'] === 'null') {
                $query->whereNull('abrigo_atual_id');
            } elseif ($filters['abrigo_id'] === 'not_null') {
                $query->whereNotNull('abrigo_atual_id');
            } else {
                $query->where('abrigo_atual_id', $filters['abrigo_id']);
            }
        }

        // Busca por nome ou CPF
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nome_responsavel', 'like', "%{$filters['search']}%")
                  ->orWhere('cpf', 'like', "%{$filters['search']}%");
            });
        }

        // Ordenação
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        if ($perPage === -1) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Beneficiario
    {
        return Beneficiario::with(['membrosFamilia', 'abrigoAtual', 'auxilios'])
                          ->find($id);
    }

    public function findByCpf(string $cpf): ?Beneficiario
    {
        return Beneficiario::where('cpf', $cpf)->first();
    }

    public function create(array $data): Beneficiario
    {
        return Beneficiario::create($data);
    }

    public function update(int $id, array $data): Beneficiario
    {
        $beneficiario = $this->findById($id);

        if (!$beneficiario) {
            throw new \DomainException("Beneficiário #{$id} não encontrado");
        }

        $beneficiario->update($data);

        return $beneficiario->fresh();
    }

    public function delete(int $id): bool
    {
        $beneficiario = $this->findById($id);

        if (!$beneficiario) {
            return false;
        }

        return $beneficiario->delete();
    }

    public function findByStatus(StatusBeneficiario $status): Collection
    {
        return Beneficiario::where('status', $status->value)->get();
    }

    public function findBySituacao(SituacaoVulnerabilidade $situacao): Collection
    {
        return Beneficiario::where('situacao_vulnerabilidade', $situacao->value)->get();
    }

    public function findEmAbrigo(): Collection
    {
        return Beneficiario::whereNotNull('abrigo_atual_id')
                          ->with('abrigoAtual')
                          ->get();
    }

    public function findForaDeAbrigo(): Collection
    {
        return Beneficiario::whereNull('abrigo_atual_id')->get();
    }

    public function findByMunicipio(int $municipioId): Collection
    {
        return Beneficiario::where('municipio_id', $municipioId)->get();
    }

    public function getStatistics(): array
    {
        // Optimized single query for basic stats
        $stats = Beneficiario::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ativos,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as inativos,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as falecidos,
            SUM(CASE WHEN abrigo_atual_id IS NOT NULL THEN 1 ELSE 0 END) as em_abrigo,
            SUM(CASE WHEN abrigo_atual_id IS NULL THEN 1 ELSE 0 END) as fora_de_abrigo,
            SUM(CASE WHEN tipo_cadastro = 'INDIVIDUAL' THEN 1 ELSE 0 END) as individuais,
            SUM(CASE WHEN tipo_cadastro = 'FAMILIAR' THEN 1 ELSE 0 END) as familiares
        ", [
            StatusBeneficiario::ATIVO->value,
            StatusBeneficiario::INATIVO->value,
            StatusBeneficiario::FALECIDO->value
        ])->first()->toArray();

        // Separate query for grouping (cannot be easily combined with single row scalar aggregation without subqueries)
        // But this handles the bulk of the counts.
        $porSituacao = Beneficiario::selectRaw('situacao_vulnerabilidade, COUNT(*) as count')
                                  ->groupBy('situacao_vulnerabilidade')
                                  ->pluck('count', 'situacao_vulnerabilidade')
                                  ->toArray();

        return array_merge($stats, [
            'por_situacao' => $porSituacao,
        ]);
    }

    public function countAtivos(): int
    {
        return Beneficiario::where('status', StatusBeneficiario::ATIVO->value)->count();
    }

    public function countTotalPessoasAssistidas(): int
    {
        // Conta beneficiários + membros da família
        $totalBeneficiarios = Beneficiario::where('status', StatusBeneficiario::ATIVO->value)->count();
        $totalMembros = Beneficiario::where('status', StatusBeneficiario::ATIVO->value)
                                   ->with('membrosFamilia')
                                   ->get()
                                   ->sum(fn($b) => $b->membrosFamilia->count());

        return $totalBeneficiarios + $totalMembros;
    }
}
