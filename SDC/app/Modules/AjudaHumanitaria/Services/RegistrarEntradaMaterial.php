<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Estoque\MovimentoEstoque;
use App\Modules\AjudaHumanitaria\Models\EntradaAh;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Recebimento de material em um deposito.
 *
 * Primeiro caminho de escrita do estoque pelo sistema novo. Ate aqui todas as
 * telas de movimento eram consulta do que veio do gestaocedec, e o ledger tinha
 * apenas os 118 lancamentos de ABERTURA da migracao.
 *
 * A entrada e os itens sao gravados junto com os lancamentos no ledger, na
 * MESMA transacao: entrada sem lancamento seria registro sem lastro no saldo, e
 * lancamento sem entrada seria saldo sem origem. Qualquer falha desfaz o
 * conjunto.
 *
 * Nada aqui escreve saldo direto. Quem reprojeta ajuda_h_estoque_saldos e o
 * RegistrarMovimentoEstoque, dono unico dessa escrita.
 */
final class RegistrarEntradaMaterial
{
    public function __construct(
        private readonly RegistrarMovimentoEstoque $ledger,
    ) {}

    /**
     * @param  array{
     *     deposito_id: int,
     *     fonte_recurso_id?: int|null,
     *     fornecedor_id?: int|null,
     *     nota_fiscal?: string|null,
     *     recebido_em: string,
     *     observacao?: string|null,
     *     itens: array<int, array{material_ah_id: int, qtd: string, valor_unitario?: string|null, data_validade?: string|null}>
     * }  $dados
     */
    public function registrar(array $dados, ?int $autorId): EntradaAh
    {
        $recebidoEm = CarbonImmutable::parse($dados['recebido_em']);

        return DB::transaction(function () use ($dados, $autorId, $recebidoEm): EntradaAh {
            $entrada = EntradaAh::create([
                'deposito_id'      => $dados['deposito_id'],
                'fonte_recurso_id' => $dados['fonte_recurso_id'] ?? null,
                'fornecedor_id'    => $dados['fornecedor_id'] ?? null,
                'nota_fiscal'      => $dados['nota_fiscal'] ?? null,
                'recebido_em'      => $recebidoEm,
                'cancelado'        => false,
                'registrado_por'   => $autorId,
                'observacao'       => $dados['observacao'] ?? null,
            ]);

            foreach ($dados['itens'] as $item) {
                $entrada->itens()->create([
                    'material_ah_id' => $item['material_ah_id'],
                    'qtd'            => $item['qtd'],
                    'valor_unitario' => $item['valor_unitario'] ?? null,
                    'data_validade'  => $item['data_validade'] ?? null,
                ]);

                $this->ledger->registrar(new MovimentoEstoque(
                    materialAhId: (int) $item['material_ah_id'],
                    depositoId: (int) $dados['deposito_id'],
                    // String, e nao float: o valor viaja intacto ate o numeric
                    // do Postgres, que faz a aritmetica.
                    quantidade: (string) $item['qtd'],
                    tipo: 'ENTRADA',
                    origemTipo: 'entrada',
                    origemId: $entrada->id,
                    registradoPor: $autorId,
                    // A data que o usuario informou, nao now(): o extrato do
                    // ledger tem de contar quando o material chegou ao
                    // deposito, e a nota costuma ser lancada dias depois.
                    ocorridoEm: $recebidoEm,
                ));
            }

            return $entrada;
        });
    }
}
