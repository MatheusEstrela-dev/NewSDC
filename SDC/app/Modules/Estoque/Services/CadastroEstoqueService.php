<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Services;

use App\Modules\Estoque\DTOs\ProdutoDTO;
use App\Modules\Estoque\Models\Almoxarifado;
use App\Modules\Estoque\Models\Kit;
use App\Modules\Estoque\Models\Lote;
use App\Modules\Estoque\Models\Produto;
use Illuminate\Support\Facades\DB;

/** Cadastros do estoque: produto, almoxarifado e composicao de kit. */
class CadastroEstoqueService
{
    public function criarProduto(ProdutoDTO $dto): Produto
    {
        return Produto::create($dto->toArray())->refresh();
    }

    public function atualizarProduto(Produto $produto, ProdutoDTO $dto): Produto
    {
        $produto->update($dto->toArray());

        return $produto->refresh();
    }

    /** Produto com saldo nao sai do catalogo: o saldo ficaria sem dono na tela. */
    public function excluirProduto(Produto $produto): void
    {
        if (Lote::query()->where('produto_id', $produto->id)->where('saldo', '>', 0)->exists()) {
            throw new \DomainException("O produto {$produto->nome} ainda tem saldo em estoque. De saida no saldo antes de excluir.");
        }

        $produto->delete();
    }

    public function criarAlmoxarifado(array $dados): Almoxarifado
    {
        return Almoxarifado::create($dados + ['ativo' => true]);
    }

    /** @param array{codigo: string, nome: string, descricao?: ?string, itens: list<array{produto_id: int, quantidade: int}>} $dados */
    public function criarKit(array $dados): Kit
    {
        return DB::transaction(function () use ($dados): Kit {
            $kit = Kit::create([
                'codigo'    => mb_strtoupper(trim($dados['codigo'])),
                'nome'      => $dados['nome'],
                'descricao' => $dados['descricao'] ?? null,
            ]);
            foreach ($dados['itens'] as $item) {
                $kit->itens()->create(['produto_id' => (int) $item['produto_id'], 'quantidade' => (int) $item['quantidade']]);
            }

            return $kit->load('itens');
        });
    }

    /** Quarentena: lote retido nao sai na saida FEFO. */
    public function alternarQuarentena(Lote $lote, bool $bloqueado): Lote
    {
        $lote->update(['bloqueado' => $bloqueado]);

        return $lote;
    }
}
