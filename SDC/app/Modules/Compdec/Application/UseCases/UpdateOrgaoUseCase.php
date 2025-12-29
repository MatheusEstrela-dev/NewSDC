<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Application\UseCases;

use App\Models\User;
use App\Modules\Compdec\Domain\Entities\Orgao;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;

class UpdateOrgaoUseCase
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository
    ) {
    }

    /**
     * Executar atualização de órgão
     */
    public function execute(int $id, array $data, User $user): Orgao
    {
        $orgao = $this->repository->find($id);

        if (!$orgao) {
            throw new \DomainException('Órgão não encontrado');
        }

        // Validar mudança de órgão superior
        if (isset($data['orgao_superior_id']) && $data['orgao_superior_id'] !== $orgao->orgao_superior_id) {
            if (!empty($data['orgao_superior_id'])) {
                $superior = $this->repository->find($data['orgao_superior_id']);
                if ($superior) {
                    $orgao->validarOrgaoSuperior($superior);
                }
            }
        }

        // Atualizar órgão
        return $this->repository->update($id, $data);
    }
}
