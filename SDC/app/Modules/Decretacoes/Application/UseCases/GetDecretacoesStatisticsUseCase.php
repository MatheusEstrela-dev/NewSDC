<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\UseCases;

use App\Modules\Decretacoes\Application\DTOs\DecretacoesStatisticsDTO;
use App\Modules\Decretacoes\Domain\Entities\Processo;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;

/**
 * UseCase: Obter estatísticas do módulo Decretações
 * Porteiro final - entrega ViewModel pronto para Inertia
 */
class GetDecretacoesStatisticsUseCase
{
    public function __construct(
        private readonly ProcessoRepositoryInterface $repository
    ) {
    }

    public function execute(): DecretacoesStatisticsDTO
    {
        try {
            $totalCount = $this->repository->count();

            if ($totalCount === 0) {
                // Usa método existente no DTO
                return DecretacoesStatisticsDTO::empty();
            }

            $vigentesCount = Processo::vigentes()->count();

            // TODO: Implementar contagem real de ECP/SE quando disponível no banco
            return new DecretacoesStatisticsDTO(
                totalEventos: $totalCount,
                totalEcp: 0,
                totalSe: $totalCount,
                registros: $totalCount,
                registrosEcp: 0,
                registrosSe: 0,
                decretacoes: $totalCount,
                decretacoesEcp: 0,
                decretacoesSe: $totalCount,
                municipiosAtingidos: $totalCount,
                municipiosEcp: 0,
                municipiosSe: $totalCount,
                decretacoesVigentes: $vigentesCount,
                vigentesEcp: 0,
                vigentesSe: $vigentesCount,
            );
        } catch (\Exception $e) {
            return DecretacoesStatisticsDTO::empty();
        }
    }
}
