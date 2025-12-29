<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Infrastructure\Persistence;

use App\Modules\Compdec\Domain\Entities\Orgao;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use App\Modules\Compdec\Domain\ValueObjects\TipoOrgao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentOrgaoRepository implements OrgaoRepositoryInterface
{
    public function find(int $id): ?Orgao
    {
        return Orgao::with(['municipio', 'orgaoSuperior', 'subordinados', 'usuarios'])->find($id);
    }

    public function findByCodigo(string $codigo): ?Orgao
    {
        return Orgao::where('codigo', $codigo)->first();
    }

    public function findByMunicipio(int $municipioId): ?Orgao
    {
        return Orgao::where('municipio_id', $municipioId)
            ->where('tipo', TipoOrgao::COMPDEC)
            ->first();
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Orgao::with(['municipio', 'orgaoSuperior'])
            ->withCount(['usuarios', 'subordinados']);

        $this->applyFilters($query, $filters);

        return $query->orderBy('tipo')
            ->orderBy('nome')
            ->paginate($perPage);
    }

    public function create(array $data): Orgao
    {
        return Orgao::create($data);
    }

    public function update(int $id, array $data): Orgao
    {
        $orgao = Orgao::findOrFail($id);
        $orgao->update($data);
        return $orgao->fresh(['municipio', 'orgaoSuperior', 'subordinados']);
    }

    public function delete(int $id): bool
    {
        $orgao = Orgao::findOrFail($id);

        // Verificar se tem subordinados
        if ($orgao->temSubordinados()) {
            throw new \DomainException('Não é possível deletar órgão com subordinados');
        }

        // Verificar se tem usuários
        if ($orgao->temUsuarios()) {
            throw new \DomainException('Não é possível deletar órgão com usuários vinculados');
        }

        return $orgao->delete();
    }

    public function findByTipo(TipoOrgao $tipo): Collection
    {
        return Orgao::where('tipo', $tipo)
            ->with(['municipio', 'orgaoSuperior'])
            ->get();
    }

    public function findSubordinados(int $orgaoId): Collection
    {
        return Orgao::where('orgao_superior_id', $orgaoId)
            ->with(['municipio'])
            ->get();
    }

    public function findOrgaoSuperior(int $orgaoId): ?Orgao
    {
        $orgao = Orgao::find($orgaoId);
        return $orgao?->orgaoSuperior;
    }

    public function getHierarquiaCompleta(int $orgaoId): array
    {
        $orgao = Orgao::with(['orgaoSuperior.orgaoSuperior'])->find($orgaoId);
        return $orgao ? $orgao->getHierarquiaCompleta() : [];
    }

    public function countByEstado(string $uf): array
    {
        return Orgao::select('tipo', DB::raw('count(*) as total'))
            ->whereHas('municipio', fn ($q) => $q->where('uf', $uf))
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();
    }

    public function findOrfaos(): Collection
    {
        return Orgao::whereNull('orgao_superior_id')
            ->where('tipo', '!=', TipoOrgao::CEDEC)
            ->with(['municipio'])
            ->get();
    }

    public function getStatistics(): array
    {
        $total = Orgao::count();
        $compdecs = Orgao::where('tipo', TipoOrgao::COMPDEC)->count();
        $redecs = Orgao::where('tipo', TipoOrgao::REDEC)->count();
        $cedecs = Orgao::where('tipo', TipoOrgao::CEDEC)->count();
        $ativos = Orgao::ativos()->count();
        $orfaos = $this->findOrfaos()->count();

        // Cobertura municipal (total de COMPDECs / total de municípios)
        $totalMunicipios = DB::table('municipios')->count();
        $percentualCobertura = $totalMunicipios > 0
            ? round(($compdecs / $totalMunicipios) * 100, 2)
            : 0;

        // Distribuição por estado
        $distribuicao = Orgao::select('municipios.uf', DB::raw('count(*) as total'))
            ->join('municipios', 'orgaos.municipio_id', '=', 'municipios.id')
            ->groupBy('municipios.uf')
            ->pluck('total', 'uf')
            ->toArray();

        return [
            'total' => $total,
            'total_compdecs' => $compdecs,
            'total_redecs' => $redecs,
            'total_cedecs' => $cedecs,
            'total_ativos' => $ativos,
            'total_orfaos' => $orfaos,
            'percentual_cobertura_municipal' => $percentualCobertura,
            'distribuicao_por_estado' => $distribuicao,
        ];
    }

    /**
     * Aplicar filtros na query
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['municipio_id'])) {
            $query->where('municipio_id', $filters['municipio_id']);
        }

        if (!empty($filters['uf'])) {
            $query->whereHas('municipio', fn ($q) => $q->where('uf', $filters['uf']));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('responsavel_nome', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['orfaos'])) {
            $query->whereNull('orgao_superior_id')
                ->where('tipo', '!=', TipoOrgao::CEDEC);
        }

        if (!empty($filters['orgao_superior_id'])) {
            $query->where('orgao_superior_id', $filters['orgao_superior_id']);
        }
    }
}
