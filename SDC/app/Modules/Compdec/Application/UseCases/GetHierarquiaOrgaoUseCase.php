<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Application\UseCases;

use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;

class GetHierarquiaOrgaoUseCase
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository
    ) {
    }

    /**
     * Obter hierarquia completa de um órgão (CEDEC → REDEC → COMPDEC)
     */
    public function execute(int $orgaoId): array
    {
        $hierarquia = $this->repository->getHierarquiaCompleta($orgaoId);

        return array_map(function ($orgao) {
            return [
                'id' => $orgao->id,
                'codigo' => $orgao->codigo,
                'nome' => $orgao->nome,
                'tipo' => $orgao->tipo->value,
                'tipo_label' => $orgao->tipo->getLabel(),
                'sigla' => $orgao->tipo->getSigla(),
                'nivel' => $orgao->tipo->getNivelHierarquico(),
                'municipio' => $orgao->municipio ? [
                    'id' => $orgao->municipio->id,
                    'nome' => $orgao->municipio->nome,
                    'uf' => $orgao->municipio->uf,
                ] : null,
            ];
        }, $hierarquia);
    }
}
