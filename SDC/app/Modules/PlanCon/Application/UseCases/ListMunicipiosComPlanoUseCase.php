<?php

namespace App\Modules\PlanCon\Application\UseCases;

use App\Modules\PlanCon\Application\DTOs\MunicipioDTO;
use App\Modules\PlanCon\Domain\Repositories\PlanoContingenciaRepositoryInterface;

class ListMunicipiosComPlanoUseCase
{
    public function __construct(
        private readonly PlanoContingenciaRepositoryInterface $repository
    ) {
    }

    public function execute(int $perPage = 15): array
    {
        $paginator = $this->repository->getMunicipiosComPlano($perPage);

        $dtos = collect($paginator->items())->map(function ($item) {
            return MunicipioDTO::fromArray([
                'id' => $item->id,
                'nome' => $item->nome,
                'codigo_ibge' => $item->codigo_ibge,
                'tem_plano' => true,
                'situacao_plano' => $item->situacao_plano,
                'data_ultima_atualizacao' => $item->data_ultima_atualizacao,
            ])->toArray();
        })->all();

        return [
            'data' => $dtos,
            'pagination' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
            ],
        ];
    }
}
