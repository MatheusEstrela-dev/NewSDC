<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Domain\Entities\Product;
use App\Modules\Tdap\Domain\Repositories\ProductRepositoryInterface;

class CreateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function execute(array $data): Product
    {
        // Validar se já existe produto com o mesmo código
        $existente = $this->productRepository->findByCodigo($data['codigo']);

        if ($existente) {
            throw new \DomainException("Já existe um produto com o código {$data['codigo']}");
        }

        return $this->productRepository->create($data);
    }
}
