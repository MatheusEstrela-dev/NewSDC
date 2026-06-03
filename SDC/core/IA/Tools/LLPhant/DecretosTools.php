<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use App\Modules\Decretacoes\Services\ProcessoQueryService;
use Illuminate\Support\Facades\DB;

class DecretosTools
{
    private function queryService(): ProcessoQueryService
    {
        return app(ProcessoQueryService::class);
    }

    /**
     * Lista os processos de decreto vinculados a um municipio mineiro. Inclui todos os status (Registro, Aguardando Nota Juridica, Reconhecido pelo Estado, Homologado etc.).
     *
     * @param int $municipio_id ID do municipio (referencia tabela municipios.id).
     * @param string $filtroStatus opcional. Filtra por status especifico (ex: "Registro", "Homologado", "Reconhecido somente pelo Estado"). Deixe vazio para todos os status.
     */
    public function listar_decretos_municipio(int $municipio_id, string $filtroStatus = ''): string
    {
        try {
            $query = DB::connection('pgsql_read')
                ->table('dec_entrada_processos as p')
                ->join('dec_decreto_municipios as m', 'p.id', '=', 'm.entrada_processos_id')
                ->where('m.municipio_id', $municipio_id)
                ->whereNull('p.deleted_at')
                ->select([
                    'p.processo',
                    'p.status',
                    'p.tipo_desastre',
                    'p.data_publicacao_mg',
                    'p.prazo_vigencia',
                    'p.n_protocolo_fide',
                    'p.reconhecimento',
                ])
                ->orderBy('p.data_publicacao_mg', 'desc')
                ->limit(20);

            if ($filtroStatus !== '') {
                $query->where('p.status', 'ilike', "%{$filtroStatus}%");
            }

            $processos = $query->get();

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
     * Busca detalhes de um processo de decretacao especifico pelo protocolo FIDE ou numero SEI. Pelo menos um dos dois parametros deve ser informado.
     *
     * @param string $n_protocolo_fide Numero do protocolo FIDE (ex: MG-F-2312312-31231-23123123). Opcional se SEI for informado.
     * @param string $processo_sei     Numero ou trecho do processo SEI inserido no sistema. Opcional se FIDE for informado.
     */
    public function consultar_processo_decreto(string $n_protocolo_fide = '', string $processo_sei = ''): string
    {
        $fide = trim($n_protocolo_fide);
        $sei  = trim($processo_sei);

        if ($fide === '' && $sei === '') {
            return json_encode([
                'encontrado' => false,
                'erro' => 'Informe ao menos um identificador: protocolo FIDE ou numero SEI.',
            ]);
        }

        try {
            $processo = DB::connection('pgsql_read')
                ->table('dec_entrada_processos as p')
                ->whereNull('p.deleted_at')
                ->where(function ($q) use ($fide, $sei) {
                    if ($fide !== '') {
                        $q->where('p.n_protocolo_fide', 'ilike', "%{$fide}%");
                    }
                    if ($sei !== '') {
                        $q->orWhere('p.processo_inserido_sei', 'ilike', "%{$sei}%");
                    }
                })
                ->select([
                    'p.id',
                    'p.processo',
                    'p.n_protocolo_fide',
                    'p.processo_inserido_sei',
                    'p.status',
                    'p.tipo_desastre',
                    'p.tipo_desastre_id',
                    'p.data_ocorrencia_desastre',
                    'p.data_publicacao_mg',
                    'p.prazo_vigencia',
                    'p.reconhecimento',
                    'p.n_decreto_estadual',
                    DB::raw('LEFT(p.observacoes, 200) AS observacoes'),
                ])
                ->first();

            if (! $processo) {
                return json_encode([
                    'encontrado' => false,
                    'criterio'   => ['fide' => $fide, 'sei' => $sei],
                ]);
            }

            $municipios = DB::connection('pgsql_read')
                ->table('dec_decreto_municipios as dm')
                ->join('municipios as m', 'dm.municipio_id', '=', 'm.id')
                ->where('dm.entrada_processos_id', $processo->id)
                ->pluck('m.nome')
                ->toArray();

            $cobrade = $this->queryService()->getTipoDesastreCompleto((int) $processo->tipo_desastre_id);
            $cobradePayload = $cobrade ? [
                'codigo'    => $cobrade['cobrade'] ?? null,
                'grupo'     => $cobrade['grupo'] ?? null,
                'subgrupo'  => $cobrade['subgrupo'] ?? null,
                'tipo'      => $cobrade['tipo'] ?? null,
                'subtipo'   => $cobrade['subtipo'] ?? null,
                'definicao' => $cobrade['definicao'] ?? null,
            ] : null;

            $danosHumanos = $this->extractDanosHumanos((int) $processo->id);

            $payload = [
                'encontrado' => true,
                'processo'   => $processo,
                'municipios' => $municipios,
            ];

            if ($cobradePayload !== null) {
                $payload['cobrade'] = $cobradePayload;
            }

            if ($danosHumanos !== null) {
                $payload['danos_humanos'] = $danosHumanos;
            }

            return json_encode($payload);
        } catch (\Exception $e) {
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Extrai apenas o resumo de danos humanos (obitos, feridos, desabrigados, desalojados)
     * agregando todos os municipios do processo. Retorna null se nao houver registros > 0.
     */
    private function extractDanosHumanos(int $processoId): ?array
    {
        try {
            $totais = $this->queryService()->calculateTotaisForProcesso($processoId);
            $geral  = $totais['geral'] ?? [];
            $danos  = $geral['danos_humanos'] ?? null;

            if (! is_array($danos) || array_sum(array_filter($danos, 'is_numeric')) === 0) {
                return null;
            }

            return $danos;
        } catch (\Throwable) {
            return null;
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
                    p.tipo_desastre_id,
                    p.reconhecimento,
                    count(*) as total,
                    max(p.data_publicacao_mg) as ultimo_decreto
                ")
                ->groupBy('p.tipo_desastre', 'p.tipo_desastre_id', 'p.reconhecimento')
                ->orderByRaw('count(*) desc')
                ->limit(10)
                ->get();

            $service = $this->queryService();
            $historicoEnriquecido = $historico->map(function ($row) use ($service) {
                $cobrade = $service->getTipoDesastreCompleto((int) $row->tipo_desastre_id);

                return [
                    'tipo_desastre'  => $row->tipo_desastre,
                    'cobrade_codigo' => $cobrade['cobrade'] ?? null,
                    'grupo'          => $cobrade['grupo'] ?? null,
                    'subgrupo'       => $cobrade['subgrupo'] ?? null,
                    'reconhecimento' => $row->reconhecimento,
                    'total'          => (int) $row->total,
                    'ultimo_decreto' => $row->ultimo_decreto,
                ];
            })->toArray();

            $totalDecretos = DB::connection('pgsql_read')
                ->table('dec_decreto_municipios')
                ->where('municipio_id', $municipio->id)
                ->count();

            return json_encode([
                'encontrado' => true,
                'municipio' => $municipio,
                'total_decretos' => $totalDecretos,
                'historico_por_tipo' => $historicoEnriquecido,
            ]);
        } catch (\Exception $e) {
            return json_encode(['encontrado' => false, 'erro' => $e->getMessage()]);
        }
    }

    /**
     * Retorna totais detalhados de danos humanos (obitos, feridos, desabrigados, desalojados), danos materiais e prejuizos economicos publicos/privados de um processo de decretacao identificado pelo protocolo FIDE.
     *
     * @param string $n_protocolo_fide Numero do protocolo FIDE (ex: MG-F-2312312-31231-23123123) ou trecho dele.
     */
    public function consultar_danos_socioeconomicos(string $n_protocolo_fide): string
    {
        $fide = trim($n_protocolo_fide);

        if ($fide === '') {
            return json_encode([
                'encontrado' => false,
                'erro' => 'Informe o protocolo FIDE do processo.',
            ]);
        }

        try {
            $processo = DB::connection('pgsql_read')
                ->table('dec_entrada_processos as p')
                ->whereNull('p.deleted_at')
                ->where('p.n_protocolo_fide', 'ilike', "%{$fide}%")
                ->select(['p.id', 'p.processo', 'p.n_protocolo_fide', 'p.tipo_desastre', 'p.status'])
                ->first();

            if (! $processo) {
                return json_encode([
                    'encontrado' => false,
                    'criterio'   => ['fide' => $fide],
                ]);
            }

            $totais = $this->queryService()->calculateTotaisForProcesso((int) $processo->id);
            $geral  = $totais['geral'] ?? [];

            $danosHumanos     = $geral['danos_humanos']     ?? [];
            $danosMateriais   = $geral['danos_materiais']   ?? [];
            $prejuizosPub     = $geral['prejuizos_publicos'] ?? [];
            $prejuizosPriv    = $geral['prejuizos_privados'] ?? [];

            $temDado = (
                array_sum(array_filter($danosHumanos, 'is_numeric')) > 0 ||
                ! empty($danosMateriais) ||
                ! empty($prejuizosPub) ||
                ! empty($prejuizosPriv)
            );

            if (! $temDado) {
                return json_encode([
                    'encontrado'  => true,
                    'processo'    => $processo,
                    'mensagem'    => 'Processo encontrado, sem registros de danos ou prejuizos cadastrados.',
                ]);
            }

            return json_encode([
                'encontrado'        => true,
                'processo'          => $processo,
                'danos_humanos'     => $danosHumanos,
                'danos_materiais'   => $danosMateriais,
                'prejuizos_publicos' => $prejuizosPub,
                'prejuizos_privados' => $prejuizosPriv,
            ]);
        } catch (\Exception $e) {
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }
}
