<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Support\Database\ConexaoPowerBi;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

/**
 * Le de bi_ajuda (sdc_powerbi) no mesmo formato de resposta dos endpoints
 * antigos de ajuda humanitaria (liberacoes/cedec e pedidos/consolidado), quando
 * SaidaBi::materializada('ajuda'). Linhas removidas ficam de fora.
 */
final class AjudaLeituraMaterializada
{
    /**
     * Formato de LiberacaoApiService::planaParaCedec().
     *
     * @return list<array<string, mixed>>
     */
    public function cedec(): array
    {
        return $this->tabela('liberacao_itens')
            ->orderBy('id')
            ->get(['municipio_id', 'codmundv', 'municipio', 'data_libera', 'quantidade', 'id_material', 'material', 'evento', 'deposito', 'status'])
            ->map(static fn (object $l): array => [
                'id_municipio' => (int) $l->municipio_id,
                'Codmundv'     => $l->codmundv === null ? null : (string) $l->codmundv,
                'municipio'    => (string) $l->municipio,
                'dataLibera'   => (string) $l->data_libera,
                'quantidade'   => (string) $l->quantidade,
                'id_material'  => $l->id_material === null ? null : (string) $l->id_material,
                'material'     => (string) $l->material,
                'evento'       => $l->evento === null ? null : (string) $l->evento,
                'deposito'     => (string) $l->deposito,
                'status'       => (int) $l->status,
            ])
            ->all();
    }

    /**
     * Formato de PedidoConsolidadoApiService::porDecreto().
     *
     * @param list<int> $statusConcluidos
     * @return array<string, list<array<string, mixed>>>
     */
    public function consolidadoPorDecreto(string $numeroDecreto, array $statusConcluidos): array
    {
        return $this->consolidado()
            ->where('num_decreto', $numeroDecreto)
            ->whereIn('status', $statusConcluidos)
            ->orderBy('descricao_item')
            ->get()
            ->map(fn (object $l): array => $this->formatar($l))
            ->groupBy('municipio')
            ->map(static fn (Collection $doMunicipio): array => $doMunicipio->values()->all())
            ->all();
    }

    /**
     * Formato de PedidoConsolidadoApiService::paraBi().
     *
     * @return list<array<string, mixed>>
     */
    public function consolidadoBi(): array
    {
        return $this->consolidado()
            ->get()
            ->map(fn (object $l): array => $this->formatar($l))
            ->all();
    }

    private function consolidado(): Builder
    {
        return $this->tabela('pedidos_itens')
            ->groupBy('status', 'status_nome', 'descricao_item', 'tp_item', 'municipio', 'num_decreto')
            ->select(['status_nome', 'descricao_item', 'tp_item', 'municipio', 'num_decreto'])
            ->selectRaw('SUM(qtd) AS total_qtd');
    }

    /** @return array<string, mixed> */
    private function formatar(object $linha): array
    {
        return [
            'status'         => $linha->status_nome,
            'descricao_item' => (string) $linha->descricao_item,
            'tp_item'        => $linha->tp_item,
            'municipio'      => (string) $linha->municipio,
            'num_decreto'    => $linha->num_decreto === null ? null : (string) $linha->num_decreto,
            'total_qtd'      => (string) $linha->total_qtd,
        ];
    }

    private function tabela(string $nome): Builder
    {
        return ConexaoPowerBi::leitura()->table("bi_ajuda.{$nome}")->where('removido', false);
    }
}
