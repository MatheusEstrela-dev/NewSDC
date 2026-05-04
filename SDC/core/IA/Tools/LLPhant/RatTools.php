<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RatTools
{
    protected RatRepositoryInterface $ratRepository;

    public function __construct()
    {
        $this->ratRepository = app(RatRepositoryInterface::class);
    }

    /**
     * Busca protocolos RAT (Registro de Atendimento Tecnico) por municipio e status
     * @param string $municipio nome do municipio mineiro (ex: Belo Horizonte, Juiz de Fora). Deixe vazio para buscar todos.
     * @param string $status filtra por status. Valores aceitos: "rascunho", "em_andamento", "finalizado", "cancelado". Deixe vazio para todos os status.
     */
    public function buscarRatPorMunicipio(string $municipio = '', string $status = ''): string
    {
        try {
            $filters = new RatFilterDTO(
                municipio: $municipio ?: null,
                status:    $status    ?: null,
                perPage:   10,
            );

            $paginator = $this->ratRepository->paginate($filters);
            $rats      = $paginator->getCollection();

            $result = $rats->map(fn ($rat) => $this->mapRatToArray($rat));

            if ($result->isEmpty()) {
                return json_encode([
                    'encontrado' => false,
                    'mensagem'   => 'Nenhum RAT encontrado para municipio=' . ($municipio ?: 'todos') . ' status=' . ($status ?: 'todos'),
                ]);
            }

            return json_encode([
                'encontrado' => true,
                'total'      => $paginator->total(),
                'rats'       => $result->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('RatTools::buscarRatPorMunicipio', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Busca um protocolo RAT especifico pelo numero do protocolo
     * @param string $protocolo numero do protocolo RAT no formato RAT-YYYY-NNNNNN (ex: RAT-2024-000001) ou apenas parte do numero
     */
    public function buscarRatPorProtocolo(string $protocolo): string
    {
        try {
            $protocolo = strtoupper(trim($protocolo));

            $filters   = new RatFilterDTO(
                protocolo: $protocolo,
                perPage:   5,
            );

            $paginator = $this->ratRepository->paginate($filters);
            $rats      = $paginator->getCollection();

            foreach ($rats as $rat) {
                if (str_contains(strtoupper($rat->protocolo), $protocolo)) {
                    return json_encode(array_merge(['encontrado' => true], $this->mapRatToArray($rat)));
                }
            }

            return json_encode([
                'encontrado' => false,
                'mensagem'   => "Nenhum RAT encontrado com protocolo: {$protocolo}",
            ]);
        } catch (\Exception $e) {
            Log::error('RatTools::buscarRatPorProtocolo', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Busca um RAT pelo ID numerico ou UUID interno do sistema. Use este metodo quando o usuario fornecer um ID exato; prefira buscarRatPorProtocolo quando tiver o numero do protocolo.
     * @param string $id identificador unico do RAT (UUID ou ID numerico)
     */
    public function buscarRatPorId(string $id): string
    {
        try {
            $rat = $this->ratRepository->findById(trim($id));

            if (!$rat) {
                return json_encode([
                    'encontrado' => false,
                    'mensagem'   => "Nenhum RAT encontrado com ID: {$id}",
                ]);
            }

            return json_encode(array_merge(
                ['encontrado' => true],
                $this->mapRatToArray($rat),
                [
                    'recursos'   => is_array($rat->recursos)   ? count($rat->recursos)   : 0,
                    'envolvidos' => is_array($rat->envolvidos) ? count($rat->envolvidos) : 0,
                ]
            ));
        } catch (\Exception $e) {
            Log::error('RatTools::buscarRatPorId', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Retorna um resumo estatistico dos protocolos RAT (Registro de Atendimento Tecnico) agrupados por status.
     * Use sempre que o usuario perguntar quantos RAT existem, quantos estao abertos, fechados, ou pedir um panorama geral dos atendimentos.
     * @param string $filtroStatus opcional. Se informado, retorna apenas a contagem do status indicado. Valores: "rascunho" (RAT abertos/em andamento), "finalizado" (RAT concluidos). Deixe vazio para listar todos os status.
     */
    public function resumoStatusRat(string $filtroStatus = ''): string
    {
        try {
            $query = DB::table('rat_ocorrencias')
                ->whereNull('deleted_at')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderBy('status', 'asc');

            $filtroNormalizado = strtolower(trim($filtroStatus));
            $statusInt = match ($filtroNormalizado) {
                'rascunho', 'aberto', 'abertos', 'em_andamento' => 0,
                'finalizado', 'finalizados', 'fechado', 'fechados', 'concluido', 'concluidos' => 1,
                default => null,
            };

            if ($statusInt !== null) {
                $query->where('status', $statusInt);
            }

            $totais = $query->get();

            if ($totais->isEmpty()) {
                return json_encode([
                    'encontrado' => false,
                    'mensagem'   => 'Nenhum RAT registrado no sistema',
                ]);
            }

            $rotulo = static fn (int $s): string => match ($s) {
                0       => 'rascunho',
                1       => 'finalizado',
                default => 'status_' . $s,
            };

            $porStatus = $totais->map(fn ($row) => [
                'status'        => (int) $row->status,
                'status_rotulo' => $rotulo((int) $row->status),
                'total'         => (int) $row->total,
            ])->values()->toArray();

            $abertos    = (int) $totais->firstWhere('status', 0)?->total;
            $finalizados = (int) $totais->firstWhere('status', 1)?->total;

            return json_encode([
                'encontrado'  => true,
                'total_geral' => $totais->sum('total'),
                'abertos'     => $abertos,
                'finalizados' => $finalizados,
                'por_status'  => $porStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('RatTools::resumoStatusRat', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    private function mapRatToArray(object $rat): array
    {
        $dadosGerais = is_array($rat->dados_gerais) ? $rat->dados_gerais : [];
        $local       = is_array($rat->local)        ? $rat->local        : [];

        return [
            'id'             => $rat->id,
            'protocolo'      => $rat->protocolo,
            'status'         => $rat->status,
            'municipio'      => $dadosGerais['local_municipio'] ?? $local['municipio'] ?? 'Nao informado',
            'tipo_ocorrencia'=> $dadosGerais['nat_nome_operacao'] ?? 'Nao informado',
            'cobrade'        => $dadosGerais['nat_cobrade_id'] ?? null,
            'data_fato'      => $dadosGerais['data_fato'] ?? null,
            'tem_vistoria'   => (bool) $rat->tem_vistoria,
            'criado_em'      => $rat->created_at?->format('d/m/Y H:i'),
            'atualizado_em'  => $rat->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
