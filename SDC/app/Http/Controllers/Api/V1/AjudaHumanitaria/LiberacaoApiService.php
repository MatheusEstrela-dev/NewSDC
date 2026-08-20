<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Paridade com os endpoints pubajudah e pubajudahCedec do Laravel legado.
 *
 * O array items vem de ajuda_h_liberacao_itens, que a carga atual nao preenche:
 * o dump do legado nao traz aju_item. Por isso items sai vazio e items_quant sai
 * zero. O contrato esta correto, a carga e que esta incompleta; quando os itens
 * entrarem, nada aqui muda.
 *
 * Somente leitura.
 */
final class LiberacaoApiService
{
    /** @var array<int, string> */
    public const SITUACOES = [
        0 => 'Aberto',
        1 => 'Pago',
        2 => 'Cancelado',
    ];

    /**
     * @return array{data: array<string, list<array<string, mixed>>>, meta: array{totais: array<string, int>}}
     */
    public function agrupadasPorAno(int $anoComeco, ?int $anoFim = null, ?string $evento = null): array
    {
        $linhas = $this->consultaBase($evento)
            ->whereRaw('EXTRACT(YEAR FROM l.data_libera) >= ?', [$anoComeco])
            ->when($anoFim !== null, fn (Builder $q): Builder => $q->whereRaw('EXTRACT(YEAR FROM l.data_libera) <= ?', [$anoFim]))
            ->orderBy('l.data_libera')
            ->get();

        return [
            'data' => $linhas
                ->groupBy(static fn (object $linha): string => (string) $linha->ano)
                ->map(fn (Collection $doAno): array => $doAno->map(fn (object $l): array => $this->formatar($l))->all())
                ->all(),
            'meta' => ['totais' => $this->totais($linhas)],
        ];
    }

    /**
     * Formato plano do pubajudahCedec: uma linha por item de liberacao.
     *
     * @return list<array<string, mixed>>
     */
    public function planaParaCedec(): array
    {
        return DB::table('ajuda_h_liberacoes as l')
            ->join('municipios as mun', 'l.municipio_id', '=', 'mun.id')
            ->join('ajuda_h_depositos as d', 'l.deposito_id', '=', 'd.id')
            ->join('ajuda_h_liberacao_itens as i', 'i.liberacao_id', '=', 'l.id')
            ->join('materiais_ah as m', 'i.material_ah_id', '=', 'm.id')
            ->whereIn('i.status', [0, 1])
            ->whereNull('l.deleted_at')
            ->select([
                'l.municipio_id as id_municipio',
                'mun.codigo_ibge as codmundv',
                'mun.nome as municipio',
                'l.data_libera',
                'i.qtd as quantidade',
                'm.codigo_legado as id_material',
                DB::raw('coalesce(m.singular, m.nome) AS material'),
                'l.evento',
                'd.nome as deposito',
                'i.status',
            ])
            ->get()
            ->map(static fn (object $l): array => [
                'id_municipio' => (int) $l->id_municipio,
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

    private function consultaBase(?string $evento): Builder
    {
        return DB::table('ajuda_h_liberacoes as l')
            ->join('municipios as mun', 'l.municipio_id', '=', 'mun.id')
            ->leftJoin('ajuda_h_liberacao_itens as i', 'i.liberacao_id', '=', 'l.id')
            ->whereNull('l.deleted_at')
            ->when($evento !== null, fn (Builder $q): Builder => $q->where('l.evento', $evento))
            ->groupBy(
                'l.id',
                'l.codigo_legado',
                'l.data_libera',
                'l.status',
                'l.evento',
                'l.payload_legado',
                'mun.id',
                'mun.codigo_ibge',
                'mun.nome',
            )
            ->select([
                'l.codigo_legado',
                'l.data_libera',
                'l.status',
                'l.evento',
                'l.payload_legado',
                'mun.id as municipio_id',
                'mun.codigo_ibge',
                'mun.nome as municipio_nome',
                DB::raw('EXTRACT(YEAR FROM l.data_libera)::int AS ano'),
                DB::raw('EXTRACT(MONTH FROM l.data_libera)::int AS mes'),
                DB::raw('coalesce(SUM(i.qtd), 0) AS items_quant'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatar(object $linha): array
    {
        $payload = $linha->payload_legado === null
            ? []
            : (array) json_decode((string) $linha->payload_legado, true);

        return [
            'id_liberacao'   => $linha->codigo_legado === null ? null : (int) $linha->codigo_legado,
            'data_liberacao' => (string) $linha->data_libera,
            'hora_liberacao' => $payload['hora_libera'] ?? null,
            'mes'            => (int) $linha->mes,
            'evento'         => $linha->evento === null ? null : (string) $linha->evento,
            'situacao'       => self::SITUACOES[(int) $linha->status] ?? 'Desconhecido',
            'unidade'        => [
                'id_municipio' => (int) $linha->municipio_id,
                'codmundv'     => $linha->codigo_ibge === null ? null : (string) $linha->codigo_ibge,
                'nome'         => (string) $linha->municipio_nome,
            ],
            'items_quant' => (int) $linha->items_quant,
            // Vazio enquanto ajuda_h_liberacao_itens nao tiver carga. O array
            // produtos do legado nao tem tabela destino no schema novo.
            'items' => [],
        ];
    }

    /**
     * @param  Collection<int, object>  $linhas
     * @return array<string, int>
     */
    private function totais(Collection $linhas): array
    {
        return [
            'total_registros'  => $linhas->count(),
            'total_pagas'      => $linhas->where('status', 1)->count(),
            'total_aberto'     => $linhas->where('status', 0)->count(),
            'total_canceladas' => $linhas->where('status', 2)->count(),
        ];
    }
}
