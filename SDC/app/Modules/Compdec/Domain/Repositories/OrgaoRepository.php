<?php

namespace App\Modules\Compdec\Domain\Repositories;

use App\Modules\Compdec\Domain\Entities\Orgao;
use App\Modules\Compdec\Models\Orgao as OrgaoModel;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrgaoRepositoryInterface
{
    public function getAll(?int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?Orgao;
    public function findByCodigo(string $codigo): ?Orgao;
    public function create(array $data): Orgao;
    public function update(int $id, array $data): Orgao;
    public function delete(int $id): bool;
    public function restore(int $id): bool;
    public function getCedecs(): array;
    public function getRedecs($cedecId = null): array;
    public function getCompdecs($redecId = null): array;
    public function getOrgaosComHierarquia(): array;
}

class OrgaoRepository implements OrgaoRepositoryInterface
{
    public function __construct(
        private OrgaoModel $model
    ) {}

    /**
     * Obter todos os órgãos com paginação
     */
    public function getAll(?int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['orgaoSuperior', 'orgaosSubordinados'])
            ->orderBy('tipo', 'asc')
            ->orderBy('nome', 'asc')
            ->paginate($perPage);
    }

    /**
     * Buscar órgão por ID
     */
    public function findById(int $id): ?Orgao
    {
        $model = $this->model
            ->with(['orgaoSuperior', 'orgaosSubordinados', 'usuarios'])
            ->find($id);

        return $model ? Orgao::fromArray($model->toArray()) : null;
    }

    /**
     * Buscar órgão por código único
     */
    public function findByCodigo(string $codigo): ?Orgao
    {
        $model = $this->model
            ->where('codigo', $codigo)
            ->first();

        return $model ? Orgao::fromArray($model->toArray()) : null;
    }

    /**
     * Criar novo órgão
     */
    public function create(array $data): Orgao
    {
        $model = $this->model->create($data);
        return Orgao::fromArray($model->fresh(['orgaoSuperior', 'usuarios'])->toArray());
    }

    /**
     * Atualizar órgão
     */
    public function update(int $id, array $data): Orgao
    {
        $model = $this->model->findOrFail($id);
        $model->update($data);

        return Orgao::fromArray($model->fresh(['orgaoSuperior', 'usuarios'])->toArray());
    }

    /**
     * Deletar órgão (soft delete)
     */
    public function delete(int $id): bool
    {
        $model = $this->model->findOrFail($id);
        return $model->delete();
    }

    /**
     * Restaurar órgão deletado
     */
    public function restore(int $id): bool
    {
        $model = $this->model->withTrashed()->findOrFail($id);
        return $model->restore();
    }

    /**
     * Obter todos os CEDECs
     */
    public function getCedecs(): array
    {
        return $this->model
            ->cedec()
            ->ativo()
            ->orderBy('nome')
            ->get()
            ->map(fn($m) => Orgao::fromArray($m->toArray()))
            ->toArray();
    }

    /**
     * Obter REDECs de um CEDEC ou todos
     */
    public function getRedecs($cedecId = null): array
    {
        $query = $this->model->redec()->ativo();

        if ($cedecId) {
            $query->where('orgao_superior_id', $cedecId);
        }

        return $query
            ->orderBy('nome')
            ->get()
            ->map(fn($m) => Orgao::fromArray($m->toArray()))
            ->toArray();
    }

    /**
     * Obter COMPDECs de um REDEC ou todos
     */
    public function getCompdecs($redecId = null): array
    {
        $query = $this->model->compdec()->ativo();

        if ($redecId) {
            $query->where('orgao_superior_id', $redecId);
        }

        return $query
            ->orderBy('nome')
            ->get()
            ->map(fn($m) => Orgao::fromArray($m->toArray()))
            ->toArray();
    }

    /**
     * Obter todos os órgãos com sua hierarquia carregada
     */
    public function getOrgaosComHierarquia(): array
    {
        return $this->model
            ->comHierarquia()
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get()
            ->map(fn($m) => Orgao::fromArray($m->toArray()))
            ->toArray();
    }
}
