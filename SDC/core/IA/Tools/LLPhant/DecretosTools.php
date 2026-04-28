<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use Illuminate\Support\Facades\DB;

class DecretosTools
{
    /**
     * Lista todos os processos de decreto vinculados a um municipio mineiro.
     *
     * @param int $municipio_id ID do municipio (referencia tabela municipios.id).
     */
    public function listar_decretos_municipio(int $municipio_id): string
    {
        try {
            $processos = DB::connection('pgsql_read')
                ->table('dec_entrada_processos as p')
                ->join('dec_decreto_municipios as m', 'p.id', '=', 'm.entrada_processos_id')
                ->where('m.municipio_id', $municipio_id)
                ->where('p.status', 'homologado')
                ->whereNull('p.deleted_at')
                ->select([
                    'p.processo',
                    'p.tipo_desastre',
                    'p.data_publicacao_mg',
                    'p.prazo_vigencia',
                    'p.n_protocolo_fide',
                    'p.reconhecimento',
                ])
                ->orderBy('p.data_publicacao_mg', 'desc')
                ->limit(20)
                ->get();

            if ($processos->isEmpty()) {
                return json_encode(['encontrado' => false, 'municipio_id' => $municipio_id]);
            }

            return json_encode([
                'encontrado' => true,
                'municipio_id' => $municipio_id,
                'total' => $processos->count(),
                'processos' => $processos->toArray(),
            ]);
        } catch (\Exception $e) {
            return json_encode(['encontrado' => false, 'erro' => $e->getMessage()]);
        }
    }

    /**
     * Busca detalhes de um processo de decretacao especifico pelo protocolo FIDE ou numero SEI.
     *
     * @param string $n_protocolo_fide Numero do protocolo FIDE (ex: FIDE-12345-2024).
     * @param string $processo_sei     Numero do processo SEI inserido no sistema.
     */
    public function consultar_processo_decreto(string $n_protocolo_fide, string $processo_sei): string
    {
        try {
            $query = DB::connection('pgsql_read')
                ->table('dec_entrada_processos as p')
                ->whereNull('p.deleted_at')
                ->select([
                    'p.id',
                    'p.processo',
                    'p.n_protocolo_fide',
                    'p.processo_inserido_sei',
                    'p.status',
                    'p.tipo_desastre',
                    'p.data_ocorrencia_desastre',
                    'p.data_entrada',
                    'p.data_publicacao_mg',
                    'p.prazo_vigencia',
                    'p.reconhecimento',
                    'p.n_decreto_estadual',
                    'p.data_decreto_estadual',
                    'p.reconhecimento_federal',
                    'p.portaria_reconhecimento_fed',
                    'p.observacoes',
                ]);

            if ($n_protocolo_fide !== '') {
                $query->where('p.n_protocolo_fide', 'ilike', "%{$n_protocolo_fide}%");
            }

            if ($processo_sei !== '') {
                $query->orWhere('p.processo_inserido_sei', 'ilike', "%{$processo_sei}%");
            }

            $processo = $query->first();

            if (! $processo) {
                return json_encode(['encontrado' => false, 'n_protocolo_fide' => $n_protocolo_fide]);
            }

            $municipios = DB::connection('pgsql_read')
                ->table('dec_decreto_municipios as dm')
                ->join('municipios as m', 'dm.municipio_id', '=', 'm.id')
                ->where('dm.entrada_processos_id', $processo->id)
                ->pluck('m.nome')
                ->toArray();

            return json_encode([
                'encontrado' => true,
                'processo' => $processo,
                'municipios' => $municipios,
            ]);
        } catch (\Exception $e) {
            return json_encode(['encontrado' => false, 'erro' => $e->getMessage()]);
        }
    }

    /**
     * Busca dados demograficos, economicos e historico de desastres de um municipio mineiro.
     *
     * @param string $nome_municipio Nome do municipio para busca (ex: Januaria).
     */
    public function obter_diagnostico_municipio(string $nome_municipio): string
    {
        try {
            $municipio = DB::connection('pgsql_read')
                ->table('municipios')
                ->where('nome', 'ilike', "%{$nome_municipio}%")
                ->where('uf', 'MG')
                ->select(['id', 'nome', 'codigo_ibge', 'mesorregiao', 'microrregiao'])
                ->first();

            if (! $municipio) {
                return json_encode(['encontrado' => false, 'municipio' => $nome_municipio]);
            }

            $historico = DB::connection('pgsql_read')
                ->table('dec_entrada_processos as p')
                ->join('dec_decreto_municipios as m', 'p.id', '=', 'm.entrada_processos_id')
                ->where('m.municipio_id', $municipio->id)
                ->whereNull('p.deleted_at')
                ->selectRaw("
                    p.tipo_desastre,
                    p.reconhecimento,
                    count(*) as total,
                    max(p.data_publicacao_mg) as ultimo_decreto
                ")
                ->groupBy('p.tipo_desastre', 'p.reconhecimento')
                ->orderByRaw('count(*) desc')
                ->limit(10)
                ->get();

            $totalDecretos = DB::connection('pgsql_read')
                ->table('dec_decreto_municipios')
                ->where('municipio_id', $municipio->id)
                ->count();

            return json_encode([
                'encontrado' => true,
                'municipio' => $municipio,
                'total_decretos' => $totalDecretos,
                'historico_por_tipo' => $historico->toArray(),
            ]);
        } catch (\Exception $e) {
            return json_encode(['encontrado' => false, 'erro' => $e->getMessage()]);
        }
    }

    /**
     * Recupera valores de prejuizos socioeconomicos e danos humanos de um processo de decreto legado.
     *
     * @param int $num_processo Numero unico do processo (dec_processo.num_processo).
     */
    public function consultar_danos_socioeconomicos(int $num_processo): string
    {
        try {
            $processo = DB::connection('pgsql_read')
                ->table('dec_processo')
                ->where('num_processo', $num_processo)
                ->select([
                    'num_processo',
                    'ano',
                    'morto',
                    'ferido',
                    'enfermo',
                    'desabrigado',
                    'desalojado',
                    'afetado',
                    'val_mat_pub_saude',
                    'val_mat_pub_ensino',
                    'val_mat_unid_hab',
                    'val_mat_obr_infr_pub',
                    'val_eco_pub',
                    'val_val_eco_priv',
                    'vl_total',
                    'stat_reconhecido',
                    'stat_homologa',
                ])
                ->first();

            if (! $processo) {
                return json_encode(['encontrado' => false, 'num_processo' => $num_processo]);
            }

            return json_encode([
                'encontrado' => true,
                'processo' => $processo,
            ]);
        } catch (\Exception $e) {
            return json_encode(['encontrado' => false, 'erro' => $e->getMessage()]);
        }
    }
}
