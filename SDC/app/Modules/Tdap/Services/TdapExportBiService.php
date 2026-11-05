<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Serializa viagens validadas do TDAP em formato flat para consumo PowerBI.
 *
 * Cada linha representa uma viagem validada com colunas denormalizadas
 * (cronograma, lote, ata, prestador, municipio, caminhao, datas, volume,
 * valores) — facil de plugar em report.
 */
class TdapExportBiService
{
    /** Linhas por pagina; para mais, paginar com `apos_id`. */
    public const LIMITE_MAXIMO = 10000;

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function viagensValidadasFlat(array $filtros = []): array
    {
        $query = DB::table('tdap_crono_viagens as v')
            ->join('tdap_crono_caminhoes as cc', 'cc.id', '=', 'v.crono_caminhao_id')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->join('tdap_caminhoes as cam', 'cam.id', '=', 'cc.caminhao_id')
            ->join('tdap_prestadores as p', 'p.id', '=', 'c.prestador_id')
            ->join('tdap_lotes as l', 'l.id', '=', 'c.lote_id')
            ->join('tdap_atas as a', 'a.id', '=', 'c.ata_id')
            ->join('municipios as m', 'm.id', '=', 'c.municipio_id')
            ->whereNull('v.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('v.validado', 1)
            ->select([
                'v.id as viagem_id',
                'v.data_registro',
                'v.data_aprovacao',
                'c.id as cronograma_id',
                'c.numero as cronograma_numero',
                'c.empenho',
                'c.nota_empenho',
                'c.dt_inicio as cronograma_inicio',
                'c.dt_final as cronograma_final',
                'a.id as ata_id',
                'a.numero as ata_numero',
                'l.id as lote_id',
                'l.numero as lote_numero',
                // Valor e capacidade gravados na aprovacao; sem eles, o cadastro.
                DB::raw('COALESCE(v.valor_m3, l.valor_m3) AS valor_m3'),
                'm.id as municipio_id',
                'm.nome as municipio_nome',
                'm.uf as municipio_uf',
                'p.id as prestador_id',
                'p.nome as prestador_nome',
                'p.cnpj as prestador_cnpj',
                'cam.id as caminhao_id',
                'cam.placa as caminhao_placa',
                DB::raw('COALESCE(v.capacidade_m3, cam.capacidade_m3) AS capacidade_m3'),
            ]);

        // Intervalo [de 00:00, ate+1 00:00) em vez de whereDate: o CAST(... AS
        // date) do whereDate anula o indice de data_aprovacao e varre a tabela.
        if ($de = $filtros['de'] ?? null) {
            $query->where('v.data_aprovacao', '>=', Carbon::parse((string) $de)->startOfDay());
        }
        if ($ate = $filtros['ate'] ?? null) {
            $query->where('v.data_aprovacao', '<', Carbon::parse((string) $ate)->startOfDay()->addDay());
        }
        // Paginacao por cursor: o cliente pede a proxima pagina com o ultimo
        // viagem_id recebido, sem OFFSET (que relê tudo o que pulou).
        if ($aposId = $filtros['apos_id'] ?? null) {
            $query->where('v.id', '>', (int) $aposId);
        }
        if ($prestadorId = $filtros['prestador_id'] ?? null) {
            $query->where('p.id', (int) $prestadorId);
        }
        // `!== null`, nao truthiness: o escopo "sem lotacao" e o municipio 0
        // (EscopoDeLeitura::SEM_ESCOPO) e tem de filtrar -- e devolver nada.
        if (($municipioId = $filtros['municipio_id'] ?? null) !== null && $municipioId !== '') {
            $query->where('m.id', (int) $municipioId);
        }

        // Teto fixo por pagina, mesmo que o request peca mais: a resposta e
        // montada em memoria no worker. Volume maior vem em varias paginas.
        $limite = min(max((int) ($filtros['limit'] ?? self::LIMITE_MAXIMO), 1), self::LIMITE_MAXIMO);

        return $query->orderBy('v.id')
            ->limit($limite)
            ->get()
            ->map(function ($row) {
                $cap = (float) ($row->capacidade_m3 ?? 0);
                $valor = (float) ($row->valor_m3 ?? 0);

                return [
                    'viagem_id'         => (int) $row->viagem_id,
                    'data_registro'     => $row->data_registro ? Carbon::parse($row->data_registro)->toIso8601String() : null,
                    'data_aprovacao'    => $row->data_aprovacao ? Carbon::parse($row->data_aprovacao)->toIso8601String() : null,
                    'cronograma_id'     => (int) $row->cronograma_id,
                    'cronograma_numero' => $row->cronograma_numero,
                    'empenho'           => $row->empenho,
                    'nota_empenho'      => $row->nota_empenho,
                    'cronograma_inicio' => $row->cronograma_inicio,
                    'cronograma_final'  => $row->cronograma_final,
                    'ata_id'            => (int) $row->ata_id,
                    'ata_numero'        => $row->ata_numero,
                    'lote_id'           => (int) $row->lote_id,
                    'lote_numero'       => $row->lote_numero,
                    'municipio_id'      => (int) $row->municipio_id,
                    'municipio_nome'    => $row->municipio_nome,
                    'municipio_uf'      => $row->municipio_uf,
                    'prestador_id'      => (int) $row->prestador_id,
                    'prestador_nome'    => $row->prestador_nome,
                    'prestador_cnpj'    => $row->prestador_cnpj,
                    'caminhao_id'       => (int) $row->caminhao_id,
                    'caminhao_placa'    => $row->caminhao_placa,
                    'capacidade_m3'     => $cap,
                    'valor_m3'          => $valor,
                    'valor_viagem'      => round($cap * $valor, 2),
                ];
            })
            ->toArray();
    }
}
