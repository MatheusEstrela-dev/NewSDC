<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Paridade com o endpoint listPedidoAh do Laravel legado.
 *
 * pedidos_ah esta vazia: o dump do legado nao traz aju_h_pedido_pedid nem
 * aju_h_pedido_itens, e essas tabelas nao estao no mapa de carga. Os dois modos
 * respondem vazio enquanto isso, e passam a responder sem alteracao de codigo
 * quando a carga existir.
 *
 * O legado gravava tramit como texto ('atendido', 'finalizado'); aqui o recorte
 * usa status inteiro, fonte unica do modulo.
 *
 * Somente leitura.
 */
final class PedidoConsolidadoApiService
{
    /** @var list<int> */
    private const STATUS_CONCLUIDOS = [
        StatusPedidoAh::Atendido->value,
        StatusPedidoAh::Finalizado->value,
    ];

    /**
     * Modo decreto_id: agrupado por municipio, so pedidos concluidos.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function porDecreto(string $numeroDecreto): array
    {
        return $this->consultaBase()
            ->where('p.numero_decreto', $numeroDecreto)
            ->whereIn('p.status', self::STATUS_CONCLUIDOS)
            ->groupBy('p.status', 'i.descricao_item', 'i.tipo', 'mun.nome', 'p.numero_decreto')
            ->orderBy('i.descricao_item')
            ->get()
            ->map(fn (object $linha): array => $this->formatar($linha))
            ->groupBy('municipio')
            ->map(static fn (Collection $doMunicipio): array => $doMunicipio->values()->all())
            ->all();
    }

    /**
     * Modo bi: lista plana, sem recorte de status.
     *
     * @return list<array<string, mixed>>
     */
    public function paraBi(): array
    {
        return $this->consultaBase()
            ->groupBy('p.status', 'i.descricao_item', 'i.tipo', 'mun.nome', 'p.numero_decreto')
            ->get()
            ->map(fn (object $linha): array => $this->formatar($linha))
            ->all();
    }

    private function consultaBase(): Builder
    {
        return DB::table('pedidos_ah as p')
            ->join('pedido_ah_itens as i', 'i.pedido_ah_id', '=', 'p.id')
            ->join('municipios as mun', 'p.municipio_id', '=', 'mun.id')
            ->whereNull('p.deleted_at')
            ->select([
                'p.status',
                // Coluna propria do item, como no legado. Nao vem de materiais_ah:
                // o texto que o municipio escreveu e preservado como foi escrito.
                'i.descricao_item',
                'i.tipo as tp_item',
                'mun.nome as municipio',
                'p.numero_decreto',
                DB::raw('SUM(i.qtd) AS total_qtd'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatar(object $linha): array
    {
        return [
            'status'         => StatusPedidoAh::tryFrom((int) $linha->status)?->name,
            'descricao_item' => (string) $linha->descricao_item,
            // tipo e char(1) no banco: o trim evita publicar espaco de padding.
            'tp_item'        => $linha->tp_item === null ? null : trim((string) $linha->tp_item),
            'municipio'      => (string) $linha->municipio,
            'num_decreto'    => $linha->numero_decreto === null ? null : (string) $linha->numero_decreto,
            'total_qtd'      => (string) $linha->total_qtd,
        ];
    }
}
