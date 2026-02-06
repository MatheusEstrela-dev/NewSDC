<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\UseCases;

use App\Modules\Decretacoes\Application\DTOs\ProcessoShowDTO;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;

/**
 * UseCase: Obter processo para visualização
 * Retorna DTO pronto para Inertia
 */
class GetProcessoShowUseCase
{
    public function __construct(
        private readonly ProcessoRepositoryInterface $repository
    ) {
    }

    public function execute(int $id): ?ProcessoShowDTO
    {
        $processo = $this->repository->findById($id);

        if (!$processo) {
            return null;
        }

        // Carrega relações necessárias
        $processo->load([
            'municipios',
            'danosHumanos.municipio',
            'danosMateriais.municipio',
            'prejuizos.municipio',
            'anexos',
            'logs.user'
        ]);

        // Converte Model -> DTO
        return ProcessoShowDTO::fromModel($processo);
    }
}
