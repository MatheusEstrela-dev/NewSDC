<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaeTools
{
    /**
     * Busca Planos de Acao de Emergencia (PAE) por municipio e status.
     * Retorna os protocolos PAE mais recentes, com dados do empreendimento e municipio vinculados.
     * @param string $municipio nome do municipio mineiro para filtrar (ex: Contagem, Betim). Deixe vazio para todos os municipios.
     * @param string $status filtra por status do PAE. Valores aceitos: "novo", "entrada_processo", "criacao_sdc", "gerenciamento", "notificacao", "analise", "aprovado", "reprovado", "ccpae", "ativo_3_anos", "suspenso", "revogado", "esperar_tratativa", "dilacao". Deixe vazio para todos.
     */
    public function buscarPaePorMunicipio(string $municipio = '', string $status = ''): string
    {
        try {
            $query = DB::table('pae_protocolos as pp')
                ->leftJoin('pae_empntos as pe', 'pp.pae_empnto_id', '=', 'pe.id')
                ->leftJoin('municipios as m', 'pe.municipio_id', '=', 'm.id')
                ->select([
                    'pp.id',
                    'pp.num_protocolo',
                    'pp.status',
                    'pp.situacao',
                    'pp.dt_entrada',
                    'pp.arquivado',
                    'pp.updated_at',
                    'pe.nome as empnto_nome',
                    'm.nome as municipio_nome',
                ])
                ->whereNull('pp.deleted_at')
                ->orderBy('pp.updated_at', 'desc')
                ->limit(10);

            if ($municipio) {
                $query->where('m.nome', 'like', '%' . $municipio . '%');
            }

            if ($status) {
                $query->where('pp.status', $status);
            }

            $results = $query->get();

            if ($results->isEmpty()) {
                return json_encode([
                    'encontrado' => false,
                    'mensagem'   => 'Nenhum PAE encontrado para municipio=' . ($municipio ?: 'todos') . ' status=' . ($status ?: 'todos'),
                ]);
            }

            return json_encode([
                'encontrado' => true,
                'total'      => $results->count(),
                'paes'       => $results->map(fn ($pae) => $this->mapPaeToArray($pae))->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('PaeTools::buscarPaePorMunicipio', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Busca um protocolo PAE especifico pelo numero de protocolo.
     * @param string $protocolo numero do protocolo PAE ou parte dele (ex: PAE-2024-000001)
     */
    public function buscarPaePorProtocolo(string $protocolo): string
    {
        try {
            $protocolo = strtoupper(trim($protocolo));

            $result = DB::table('pae_protocolos as pp')
                ->leftJoin('pae_empntos as pe', 'pp.pae_empnto_id', '=', 'pe.id')
                ->leftJoin('municipios as m', 'pe.municipio_id', '=', 'm.id')
                ->select([
                    'pp.id',
                    'pp.num_protocolo',
                    'pp.status',
                    'pp.situacao',
                    'pp.dt_entrada',
                    'pp.limite_analise',
                    'pp.ccpae_venc',
                    'pp.sei_numero',
                    'pp.arquivado',
                    'pp.obs',
                    'pp.updated_at',
                    'pe.nome as empnto_nome',
                    'm.nome as municipio_nome',
                ])
                ->whereNull('pp.deleted_at')
                ->where('pp.num_protocolo', 'like', '%' . $protocolo . '%')
                ->orderBy('pp.updated_at', 'desc')
                ->first();

            if (!$result) {
                return json_encode([
                    'encontrado' => false,
                    'mensagem'   => "Nenhum PAE encontrado com protocolo: {$protocolo}",
                ]);
            }

            return json_encode(array_merge(
                ['encontrado' => true],
                $this->mapPaeToArray($result),
                [
                    'limite_analise' => $result->limite_analise,
                    'ccpae_venc'     => $result->ccpae_venc,
                    'sei_numero'     => $result->sei_numero,
                    'obs'            => $result->obs,
                ]
            ));
        } catch (\Exception $e) {
            Log::error('PaeTools::buscarPaePorProtocolo', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Retorna um resumo estatistico dos protocolos PAE agrupados por status.
     * Util para entender a distribuicao atual dos PAEs no sistema.
     */
    public function resumoStatusPae(): string
    {
        try {
            $totais = DB::table('pae_protocolos')
                ->whereNull('deleted_at')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderBy('total', 'desc')
                ->get();

            if ($totais->isEmpty()) {
                return json_encode([
                    'encontrado' => false,
                    'mensagem'   => 'Nenhum PAE registrado no sistema',
                ]);
            }

            return json_encode([
                'encontrado'  => true,
                'total_geral' => $totais->sum('total'),
                'por_status'  => $totais->map(fn ($row) => [
                    'status' => $row->status,
                    'total'  => (int) $row->total,
                ])->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('PaeTools::resumoStatusPae', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    private function mapPaeToArray(object $pae): array
    {
        return [
            'id'          => $pae->id,
            'protocolo'   => $pae->num_protocolo,
            'status'      => $pae->status,
            'situacao'    => $pae->situacao ?? null,
            'municipio'   => $pae->municipio_nome ?? 'Nao informado',
            'empreendimento' => $pae->empnto_nome ?? 'Nao informado',
            'dt_entrada'  => $pae->dt_entrada,
            'arquivado'   => (bool) $pae->arquivado,
            'atualizado_em' => $pae->updated_at,
        ];
    }
}
