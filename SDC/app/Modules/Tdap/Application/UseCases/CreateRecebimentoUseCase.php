<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Domain\Entities\Recebimento;
use App\Modules\Tdap\Domain\Repositories\RecebimentoRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\ProductLoteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateRecebimentoUseCase
{
    public function __construct(
        private readonly RecebimentoRepositoryInterface $recebimentoRepository,
        private readonly ProductLoteRepositoryInterface $loteRepository,
    ) {}

    public function execute(array $data): Recebimento
    {
        return DB::transaction(function () use ($data) {
            // Criar o recebimento principal
            $recebimentoData = [
                'ordem_compra_id' => $data['ordem_compra_id'] ?? null,
                'nota_fiscal' => $data['nota_fiscal'],
                'placa_veiculo' => $data['placa_veiculo'],
                'transportadora' => $data['transportadora'] ?? null,
                'motorista_nome' => $data['motorista_nome'],
                'motorista_documento' => $data['motorista_documento'] ?? null,
                'doca_descarga' => $data['doca_descarga'] ?? null,
                'data_chegada' => $data['data_chegada'] ?? now(),
                'observacoes' => $data['observacoes'] ?? null,
            ];

            $recebimento = $this->recebimentoRepository->create($recebimentoData);

            // Criar os itens do recebimento
            if (isset($data['itens']) && is_array($data['itens'])) {
                foreach ($data['itens'] as $item) {
                    $recebimento->itens()->create($item);
                }
            }

            return $recebimento->fresh(['itens', 'itens.product']);
        });
    }
}
