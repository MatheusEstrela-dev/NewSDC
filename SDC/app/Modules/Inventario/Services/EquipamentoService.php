<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Contracts\EquipamentoRepository;
use App\Modules\Inventario\DTOs\EquipamentoData;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Models\Equipamento;

/**
 * Escritas do cadastro de equipamentos. Antes o EquipamentoController gravava
 * validated() direto no Model; as regras de movimentacao ativa vieram junto.
 */
class EquipamentoService
{
    public function __construct(private readonly EquipamentoRepository $equipamentos) {}

    public function criar(EquipamentoData $dados): Equipamento
    {
        $equipamento = new Equipamento($dados->toArray());
        $equipamento->situacao = SituacaoEquipamento::DISPONIVEL;

        return $this->equipamentos->save($equipamento);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\HttpException 404 ou 409 */
    public function atualizar(int $id, EquipamentoData $dados): Equipamento
    {
        $equipamento = $this->equipamentos->find($id) ?? abort(404);

        abort_if(
            $equipamento->movimentacoes()->seguraEquipamento()->exists()
                && $dados->quantidade !== (int) $equipamento->quantidade,
            409,
            'Não é possível alterar a quantidade durante uma movimentação ativa.'
        );

        $equipamento->fill($dados->toArray());

        return $this->equipamentos->save($equipamento);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\HttpException 404 ou 409 */
    public function remover(int $id): void
    {
        $equipamento = $this->equipamentos->find($id) ?? abort(404);
        abort_if($equipamento->movimentacoes()->seguraEquipamento()->exists(), 409, 'Há movimentação ativa para este equipamento.');

        $equipamento->delete();
    }
}
