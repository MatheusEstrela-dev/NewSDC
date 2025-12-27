<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Application\DTOs\DashboardDataDTO;
use App\Modules\Tdap\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\ProductLoteRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\RecebimentoRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\MovimentacaoRepositoryInterface;

class GetDashboardDataUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductLoteRepositoryInterface $loteRepository,
        private readonly RecebimentoRepositoryInterface $recebimentoRepository,
        private readonly MovimentacaoRepositoryInterface $movimentacaoRepository,
    ) {}

    public function execute(): DashboardDataDTO
    {
        // Estatísticas gerais
        $productStatistics = $this->productRepository->getStatistics();
        $recebimentoStatistics = $this->recebimentoRepository->getStatistics();
        $movimentacaoStatistics = $this->movimentacaoRepository->getStatistics();

        // Alertas
        $produtosEstoqueBaixo = $this->productRepository->findComEstoqueBaixo();
        $lotesVencidos = $this->loteRepository->findVencidos();
        $lotesProximosVencimento = $this->loteRepository->findProximosVencimento(30);
        $recebimentosPendentes = $this->recebimentoRepository->findPendentesConferencia();

        $alertas = [
            'produtos_estoque_baixo' => $produtosEstoqueBaixo->count(),
            'lotes_vencidos' => $lotesVencidos->count(),
            'lotes_proximos_vencimento' => $lotesProximosVencimento->count(),
            'recebimentos_pendentes' => $recebimentosPendentes->count(),
        ];

        $statistics = [
            'total_produtos' => $productStatistics['total'] ?? 0,
            'recebimentos_finalizados' => $recebimentoStatistics['finalizados'] ?? 0,
            'recebimentos_pendentes' => $recebimentoStatistics['pendentes'] ?? 0,
            'movimentacoes_mes' => $movimentacaoStatistics['total'] ?? 0,
        ];

        return new DashboardDataDTO(
            statistics: $statistics,
            alertas: $alertas,
            produtosEstoqueBaixo: $produtosEstoqueBaixo->count(),
            lotesVencidos: $lotesVencidos->count(),
            lotesProximosVencimento: $lotesProximosVencimento->count(),
            recebimentosPendentes: $recebimentosPendentes->count(),
        );
    }
}
