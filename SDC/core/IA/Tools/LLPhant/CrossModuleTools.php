<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrossModuleTools
{
    use ResolvesMunicipio;

    /**
     * Panorama de um municipio mineiro em todos os modulos do NewSDC: processos de decretacao, RATs e protocolos PAE. Use quando o usuario pedir uma visao geral de um municipio.
     *
     * @param string $nome_municipio nome do municipio (ex: Januaria, Belo Horizonte).
     */
    public function panorama_municipio(string $nome_municipio): string
    {
        try {
            $municipio = $this->resolverMunicipio($nome_municipio);

            if (! $municipio) {
                return $this->municipioNaoEncontrado($nome_municipio);
            }

            return json_encode([
                'encontrado' => true,
                'municipio'  => $municipio,
                'decretos'   => $this->decretos((int) $municipio->id),
                'rat'        => $this->rats($municipio->nome),
                'pae'        => $this->paes((int) $municipio->id),
            ]);
        } catch (\Exception $e) {
            Log::error('CrossModuleTools::panorama_municipio', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    private function decretos(int $municipioId): array
    {
        $base = fn () => DB::connection('pgsql_ai')
            ->table('dec_entrada_processos as p')
            ->join('dec_decreto_municipios as dm', 'dm.entrada_processos_id', '=', 'p.id')
            ->where('dm.municipio_id', $municipioId)
            ->whereNull('p.deleted_at')
            ->whereNull('dm.deleted_at');

        $ultimo = $base()
            ->orderByDesc('p.data_publicacao_mg')
            ->first(['p.n_protocolo_fide', 'p.tipo_desastre', 'p.status', 'p.data_publicacao_mg', 'p.prazo_vigencia']);

        return [
            'total'  => $base()->distinct()->count('p.id'),
            'ultimo' => $ultimo,
        ];
    }

    private function rats(string $municipioNome): array
    {
        $porStatus = DB::connection('pgsql_ai')
            ->table('rat_ocorrencias as o')
            ->join('rat_relato_dados_gerais as dg', 'dg.ocorrencia_id', '=', 'o.id')
            ->whereNull('o.deleted_at')
            ->whereNull('dg.deleted_at')
            ->whereRaw('LOWER(dg.local_municipio_nome) = LOWER(?)', [$municipioNome])
            ->selectRaw('o.status, COUNT(DISTINCT o.id) AS total')
            ->groupBy('o.status')
            ->pluck('total', 'status');

        return [
            'total'       => (int) $porStatus->sum(),
            'abertos'     => (int) ($porStatus[0] ?? 0),
            'finalizados' => (int) ($porStatus[1] ?? 0),
        ];
    }

    private function paes(int $municipioId): array
    {
        $porStatus = DB::connection('pgsql_ai')
            ->table('pae_protocolos as pp')
            ->join('pae_empntos as pe', 'pe.id', '=', 'pp.pae_empnto_id')
            ->where('pe.municipio_id', $municipioId)
            ->whereNull('pp.deleted_at')
            ->whereNull('pe.deleted_at')
            ->selectRaw('pp.status, COUNT(*) AS total')
            ->groupBy('pp.status')
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        return [
            'total'      => (int) $porStatus->sum('total'),
            'por_status' => $porStatus->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total])->values()->toArray(),
        ];
    }
}
