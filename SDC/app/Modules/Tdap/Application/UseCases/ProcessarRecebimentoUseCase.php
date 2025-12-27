<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Domain\Entities\Recebimento;
use App\Modules\Tdap\Domain\Repositories\RecebimentoRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\ProductLoteRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\MovimentacaoRepositoryInterface;
use App\Modules\Tdap\Domain\ValueObjects\MovimentacaoType;
use Illuminate\Support\Facades\DB;

/**
 * Processa um recebimento aprovado, criando lotes e movimentações
 */
class ProcessarRecebimentoUseCase
{
    public function __construct(
        private readonly RecebimentoRepositoryInterface $recebimentoRepository,
        private readonly ProductLoteRepositoryInterface $loteRepository,
        private readonly MovimentacaoRepositoryInterface $movimentacaoRepository,
    ) {}

    public function execute(int $recebimentoId, int $userId): Recebimento
    {
        return DB::transaction(function () use ($recebimentoId, $userId) {
            $recebimento = $this->recebimentoRepository->findById($recebimentoId);

            if (!$recebimento) {
                throw new \DomainException("Recebimento #{$recebimentoId} não encontrado");
            }

            // Aprovar o recebimento
            $recebimento->aprovar($userId);

            // Para cada item do recebimento, criar/atualizar lote e movimentação
            foreach ($recebimento->itens as $item) {
                // Buscar ou criar lote
                $loteData = [
                    'product_id' => $item->product_id,
                    'numero_lote' => $item->numero_lote,
                    'data_entrada' => now(),
                    'data_fabricacao' => $item->data_fabricacao,
                    'data_validade' => $item->data_validade,
                    'quantidade_inicial' => $item->quantidadeAceita(),
                    'quantidade_atual' => $item->quantidadeAceita(),
                    'localizacao' => null, // Será definido posteriormente
                    'observacoes' => "Recebimento {$recebimento->numero_recebimento}",
                ];

                $lote = $this->loteRepository->create($loteData);

                // Criar movimentação de entrada
                $movimentacaoData = [
                    'tipo' => MovimentacaoType::ENTRADA,
                    'product_id' => $item->product_id,
                    'lote_id' => $lote->id,
                    'quantidade' => $item->quantidadeAceita(),
                    'data_movimentacao' => now(),
                    'origem' => $recebimento->transportadora ?? 'Fornecedor',
                    'destino' => 'Depósito',
                    'responsavel_id' => $userId,
                    'documento_referencia' => $recebimento->nota_fiscal,
                    'observacoes' => "Recebimento {$recebimento->numero_recebimento}",
                ];

                $this->movimentacaoRepository->create($movimentacaoData);
            }

            // Finalizar o recebimento
            $recebimento->finalizar();

            return $recebimento->fresh(['itens', 'itens.product']);
        });
    }
}
