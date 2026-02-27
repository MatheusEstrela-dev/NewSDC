<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Models\Beneficiario;
use App\Modules\AjudaHumanitaria\Enums\StatusBeneficiario;
use App\Modules\AjudaHumanitaria\Enums\SituacaoVulnerabilidade;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BeneficiarioService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Beneficiario::query()->with(['membrosFamilia', 'abrigoAtual']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['situacao_vulnerabilidade'])) {
            $query->where('situacao_vulnerabilidade', $filters['situacao_vulnerabilidade']);
        }

        if (!empty($filters['tipo_cadastro'])) {
            $query->where('tipo_cadastro', $filters['tipo_cadastro']);
        }

        if (!empty($filters['municipio_id'])) {
            $query->where('municipio_id', $filters['municipio_id']);
        }

        if (isset($filters['abrigo_id'])) {
            if ($filters['abrigo_id'] === 'null') {
                $query->whereNull('abrigo_atual_id');
            } elseif ($filters['abrigo_id'] === 'not_null') {
                $query->whereNotNull('abrigo_atual_id');
            } else {
                $query->where('abrigo_atual_id', $filters['abrigo_id']);
            }
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nome_responsavel', 'like', "%{$filters['search']}%")
                  ->orWhere('cpf', 'like', "%{$filters['search']}%");
            });
        }

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
        return Beneficiario::with(['membrosFamilia', 'abrigoAtual', 'auxilios'])->find($id);
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
            throw new \DomainException("Beneficiario #{$id} nao encontrado");
        }

        $beneficiario->update($data);
        return $beneficiario->fresh();
    }

    public function delete(int $id): bool
    {
        $beneficiario = $this->findById($id);
        return $beneficiario ? $beneficiario->delete() : false;
    }

    public function getStatistics(): array
    {
        $stats = Beneficiario::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ativos,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as inativos,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as falecidos,
            SUM(CASE WHEN abrigo_atual_id IS NOT NULL THEN 1 ELSE 0 END) as em_abrigo,
            SUM(CASE WHEN abrigo_atual_id IS NULL THEN 1 ELSE 0 END) as fora_de_abrigo
        ", [
            StatusBeneficiario::ATIVO->value,
            StatusBeneficiario::INATIVO->value,
            StatusBeneficiario::FALECIDO->value
        ])->first()->toArray();

        $porSituacao = Beneficiario::selectRaw('situacao_vulnerabilidade, COUNT(*) as count')
                                  ->groupBy('situacao_vulnerabilidade')
                                  ->pluck('count', 'situacao_vulnerabilidade')
                                  ->toArray();

        return array_merge($stats, ['por_situacao' => $porSituacao]);
    }
}
