<?php

namespace App\Modules\Compdec\Services;

use App\Modules\Compdec\Domain\Entities\Orgao;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepository;
use App\Modules\Compdec\DTOs\OrgaoDTO;
use Illuminate\Support\Facades\DB;

class OrgaoService
{
    public function __construct(
        private OrgaoRepository $repository
    ) {}

    /**
     * Listar todos os órgãos com paginação
     */
    public function listarOrgaos(?int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->repository->getAll($perPage);
    }

    /**
     * Obter órgão por ID com validação
     */
    public function obterOrgao(int $id): Orgao
    {
        $orgao = $this->repository->findById($id);

        if (!$orgao) {
            throw new \Exception("Órgão não encontrado com ID: {$id}");
        }

        return $orgao;
    }

    /**
     * Criar novo órgão
     */
    public function criarOrgao(OrgaoDTO $dto): Orgao
    {
        return DB::transaction(function () use ($dto) {
            // Validar hierarquia se for REDEC ou COMPDEC
            if ($dto->tipo !== 'cedec' && $dto->orgaoSuperiorId) {
                $orgaoSuperior = $this->repository->findById($dto->orgaoSuperiorId);
                if (!$orgaoSuperior) {
                    throw new \Exception("Órgão superior não encontrado");
                }

                // Validar níveis hierárquicos
                if ($dto->tipo === 'redec' && !$orgaoSuperior->isCedec()) {
                    throw new \Exception("REDEC deve estar vinculada a um CEDEC");
                }
                if ($dto->tipo === 'compdec' && !$orgaoSuperior->isRedec()) {
                    throw new \Exception("COMPDEC deve estar vinculada a um REDEC");
                }
            }

            return $this->repository->create($dto->toArray());
        });
    }

    /**
     * Atualizar órgão existente
     */
    public function atualizarOrgao(int $id, OrgaoDTO $dto): Orgao
    {
        return DB::transaction(function () use ($id, $dto) {
            $orgao = $this->repository->findById($id);

            if (!$orgao) {
                throw new \Exception("Órgão não encontrado");
            }

            // Validar hierarquia se for REDEC ou COMPDEC
            if ($dto->tipo !== 'cedec' && $dto->orgaoSuperiorId !== $orgao->orgaoSuperiorId) {
                $orgaoSuperior = $this->repository->findById($dto->orgaoSuperiorId);
                if (!$orgaoSuperior) {
                    throw new \Exception("Órgão superior não encontrado");
                }

                // Validar níveis hierárquicos
                if ($dto->tipo === 'redec' && !$orgaoSuperior->isCedec()) {
                    throw new \Exception("REDEC deve estar vinculada a um CEDEC");
                }
                if ($dto->tipo === 'compdec' && !$orgaoSuperior->isRedec()) {
                    throw new \Exception("COMPDEC deve estar vinculada a um REDEC");
                }
            }

            return $this->repository->update($id, $dto->toArray());
        });
    }

    /**
     * Deletar órgão
     */
    public function deletarOrgao(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $orgao = $this->repository->findById($id);

            if (!$orgao) {
                throw new \Exception("Órgão não encontrado");
            }

            // Validar se tem subordinados antes de deletar
            // Isso pode ser permitido ou não dependendo das regras de negócio

            return $this->repository->delete($id);
        });
    }

    /**
     * Restaurar órgão deletado
     */
    public function restaurarOrgao(int $id): bool
    {
        return $this->repository->restore($id);
    }

    /**
     * Obter CEDECs para dropdown
     */
    public function obterCedecs(): array
    {
        return $this->repository->getCedecs();
    }

    /**
     * Obter REDECs para dropdown
     */
    public function obterRedecs(?int $cedecId = null): array
    {
        return $this->repository->getRedecs($cedecId);
    }

    /**
     * Obter COMPDECs para dropdown
     */
    public function obterCompdecs(?int $redecId = null): array
    {
        return $this->repository->getCompdecs($redecId);
    }

    /**
     * Obter árvore hierárquica de órgãos
     */
    public function obterArvorHierarquica(): array
    {
        $orgaos = $this->repository->getOrgaosComHierarquia();

        // Agrupar por CEDEC
        $arvore = [];
        foreach ($orgaos as $orgao) {
            if ($orgao->isCedec()) {
                $arvore[$orgao->id] = [
                    'orgao' => $orgao,
                    'redecs' => [],
                ];
            }
        }

        // Adicionar REDECs aos CEDECs
        foreach ($orgaos as $orgao) {
            if ($orgao->isRedec() && $orgao->orgaoSuperiorId) {
                if (isset($arvore[$orgao->orgaoSuperiorId])) {
                    $arvore[$orgao->orgaoSuperiorId]['redecs'][$orgao->id] = [
                        'orgao' => $orgao,
                        'compdecs' => [],
                    ];
                }
            }
        }

        // Adicionar COMPDECs aos REDECs
        foreach ($orgaos as $orgao) {
            if ($orgao->isCompdec() && $orgao->orgaoSuperiorId) {
                foreach ($arvore as &$cedec) {
                    if (isset($cedec['redecs'][$orgao->orgaoSuperiorId])) {
                        $cedec['redecs'][$orgao->orgaoSuperiorId]['compdecs'][] = $orgao;
                    }
                }
            }
        }

        return $arvore;
    }

    /**
     * Vincular usuário a um órgão
     */
    public function vincularUsuarioAOrgao(int $orgaoId, int $usuarioId, array $roles = []): bool
    {
        $orgao = $this->repository->findById($orgaoId);

        if (!$orgao) {
            throw new \Exception("Órgão não encontrado");
        }

        return DB::transaction(function () use ($orgaoId, $usuarioId, $roles) {
            // Usar o modelo para vincular via relacionamento many-to-many
            $model = \App\Modules\Compdec\Models\Orgao::findOrFail($orgaoId);

            // Sincronizar usuários (atualizar se já existe)
            return (bool)$model->usuarios()->syncWithoutDetaching([$usuarioId]);
        });
    }
}
