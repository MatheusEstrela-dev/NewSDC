<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use Illuminate\Support\Facades\DB;

/**
 * Leitura dos itens de pedidos de ajuda humanitaria agrupados por decreto.
 *
 * Consumido pela tela de Decretacoes. Substitui a leitura das tabelas do
 * legado (aju_h_pedido_pedid / aju_h_pedido_itens) pelas tabelas do modulo
 * (pedidos_ah / pedido_ah_itens). Vinculo: pedidos_ah.numero_decreto =
 * processo.decreto_municipal.
 *
 * Mapeamento legado -> novo:
 *  - num_decreto    -> pedidos_ah.numero_decreto
 *  - tramit         -> pedidos_ah.status (inteiro; exibido pelo label do enum)
 *  - tp_item        -> pedido_ah_itens.tipo, ou 'RECEBIDO' quando o pedido esta Atendido
 *  - qtd            -> pedido_ah_itens.qtd
 *
 * Somente leitura.
 */
final class PedidosAhPorDecretoService
{
    public const TIPO_RECEBIDO = 'RECEBIDO';

    /**
     * @param  list<string>  $decretos  Numeros de decreto (decreto_municipal)
     * @return array<string, array<string, list<array<string, mixed>>>> decreto => codigo => linhas
     */
    public function porDecretos(array $decretos): array
    {
        if ($decretos === []) {
            return [];
        }

        $registros = DB::table('pedidos_ah as p')
            ->join('pedido_ah_itens as i', 'i.pedido_ah_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.numero_decreto', $decretos)
            ->select([
                'p.numero_decreto',
                'i.codigo',
                'p.status',
                'i.descricao_item',
                'i.tipo',
                DB::raw('SUM(i.qtd) AS total_qtd'),
            ])
            ->groupBy('p.numero_decreto', 'i.codigo', 'p.status', 'i.descricao_item', 'i.tipo')
            ->orderBy('i.descricao_item')
            ->get();

        $resultado = [];
        foreach ($registros as $linha) {
            $status = StatusPedidoAh::tryFrom((int) $linha->status);
            $codigo = (string) $linha->codigo;

            $resultado[(string) $linha->numero_decreto][$codigo][] = [
                'codigo'         => $linha->codigo,
                'status'         => $status?->label(),
                'descricao_item' => $linha->descricao_item,
                // tipo e char(1): o trim evita espaco de padding.
                'tp_item'        => $status === StatusPedidoAh::Atendido
                    ? self::TIPO_RECEBIDO
                    : trim((string) $linha->tipo),
                'total_qtd'      => $linha->total_qtd,
            ];
        }

        return $resultado;
    }
}
