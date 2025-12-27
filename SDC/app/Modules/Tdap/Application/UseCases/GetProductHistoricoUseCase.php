<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Domain\Repositories\MovimentacaoRepositoryInterface;
use Illuminate\Support\Collection;

class GetProductHistoricoUseCase
{
    public function __construct(
        private readonly MovimentacaoRepositoryInterface $movimentacaoRepository
    ) {}

    public function execute(int $productId): Collection
    {
        return $this->movimentacaoRepository->getHistoricoProduct($productId);
    }
}
