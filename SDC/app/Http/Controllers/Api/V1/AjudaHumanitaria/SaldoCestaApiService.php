<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Paridade com o endpoint saldocesta do Laravel legado.
 *
 * O legado filtrava aju_unidade.categoria = 'CESTA BASICA' e saldo <> 0,
 * agrupando por deposito, e truncava o peso com floor(). O recorte aqui e o
 * mesmo, sobre as tabelas do banco novo.
 *
 * aju_unidade.singular veio esparso do legado (5 de 187 materiais). Onde falta,
 * o nome do material ocupa o lugar: e melhor devolver o nome do que nulo em um
 * campo que o consumidor usa como rotulo.
 *
 * Somente leitura.
 */
final class SaldoCestaApiService
{
    public const CATEGORIA = 'CESTA BASICA';

    /** Mesmo recorte do consultar(), sem agrupar por nome: uma linha por deposito e material. */
    public function consultaPorMaterial(): Builder
    {
        return DB::table('ajuda_h_estoque_saldos as s')
            ->join('ajuda_h_depositos as d', 's.deposito_id', '=', 'd.id')
            ->join('materiais_ah as m', 's.material_ah_id', '=', 'm.id')
            ->where('m.categoria', self::CATEGORIA)
            ->where('s.saldo', '<>', 0)
            ->select([
                's.deposito_id',
                's.material_ah_id',
                'd.nome as deposito',
                DB::raw('coalesce(m.singular, m.nome) AS material'),
                's.saldo',
                'm.valor',
                DB::raw('FLOOR(m.peso) AS peso'),
            ]);
    }

    /**
     * Linha de bi_ajuda.saldos_cesta a partir de consultaPorMaterial().
     *
     * @return array<string, mixed>
     */
    public function linhaParaBi(object $linha): array
    {
        return [
            'deposito_id'    => (int) $linha->deposito_id,
            'material_ah_id' => (int) $linha->material_ah_id,
            'deposito'       => (string) $linha->deposito,
            'material'       => (string) $linha->material,
            'saldo'          => (string) $linha->saldo,
            'valor'          => $linha->valor === null ? null : (string) $linha->valor,
            'peso'           => $linha->peso === null ? null : (int) $linha->peso,
        ];
    }

    /**
     * @return list<array{id_deposito: int, nome: string, total_saldo: string, singular: string, valor: string|null, peso: int|null}>
     */
    public function consultar(): array
    {
        return DB::table('ajuda_h_estoque_saldos as s')
            ->join('ajuda_h_depositos as d', 's.deposito_id', '=', 'd.id')
            ->join('materiais_ah as m', 's.material_ah_id', '=', 'm.id')
            ->where('m.categoria', self::CATEGORIA)
            ->where('s.saldo', '<>', 0)
            ->groupBy('s.deposito_id', 'd.nome', 'm.singular', 'm.nome', 'm.valor', 'm.peso')
            ->orderBy('d.nome')
            ->select([
                's.deposito_id as id_deposito',
                'd.nome',
                DB::raw('SUM(s.saldo) AS total_saldo'),
                DB::raw('coalesce(m.singular, m.nome) AS singular'),
                'm.valor',
                DB::raw('FLOOR(m.peso) AS peso'),
            ])
            ->get()
            ->map(static fn (object $linha): array => [
                'id_deposito' => (int) $linha->id_deposito,
                'nome'        => (string) $linha->nome,
                'total_saldo' => (string) $linha->total_saldo,
                'singular'    => (string) $linha->singular,
                'valor'       => $linha->valor === null ? null : (string) $linha->valor,
                'peso'        => $linha->peso === null ? null : (int) $linha->peso,
            ])
            ->all();
    }
}
