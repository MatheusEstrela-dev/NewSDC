<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaeAnalyticsTools
{
    use ResolvesMunicipio;

    /** Ano de entrada do protocolo; sem data de entrada, conta pelo ano de criacao. */
    private const ANO = 'EXTRACT(YEAR FROM COALESCE(pp.dt_entrada, pp.created_at))::int';

    /**
     * Ranking dos municipios mineiros com mais protocolos PAE (Plano de Acao de Emergencia), pelo municipio do empreendimento.
     *
     * @param string $status opcional. Filtra por status do PAE (ex: "analise", "aprovado", "ccpae", "notificacao"). Deixe vazio para todos.
     * @param int $limite quantidade de municipios no ranking (padrao 10, maximo 50).
     */
    public function ranking_municipios_pae(string $status = '', int $limite = 10): string
    {
        try {
            $query = $this->protocolosPorMunicipio()
                ->selectRaw('m.nome AS municipio, COUNT(DISTINCT pp.id) AS total, COUNT(DISTINCT pe.id) AS empreendimentos')
                ->groupBy('m.nome')
                ->orderByRaw('COUNT(DISTINCT pp.id) DESC')
                ->limit(max(1, min($limite, 50)));

            if (trim($status) !== '') {
                $query->where('pp.status', trim($status));
            }

            $ranking = $query->get();

            if ($ranking->isEmpty()) {
                return json_encode(['encontrado' => false, 'status' => $status ?: 'todos']);
            }

            return json_encode([
                'encontrado' => true,
                'status'     => $status ?: 'todos',
                'ranking'    => $ranking->map(fn ($r, $i) => [
                    'posicao'         => $i + 1,
                    'municipio'       => $r->municipio,
                    'total'           => (int) $r->total,
                    'empreendimentos' => (int) $r->empreendimentos,
                ])->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('PaeAnalyticsTools::ranking_municipios_pae', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Quantidade de protocolos PAE por ano de entrada, no estado inteiro ou em um municipio.
     *
     * @param string $nome_municipio opcional. Nome do municipio do empreendimento (ex: Brumadinho). Deixe vazio para o estado inteiro.
     */
    public function tendencia_anual_pae(string $nome_municipio = ''): string
    {
        try {
            $query = $this->protocolosPorMunicipio()
                ->selectRaw(self::ANO . ' AS ano, COUNT(DISTINCT pp.id) AS total')
                ->groupByRaw('1')
                ->orderByRaw('1');

            $escopo = 'Minas Gerais';

            if (trim($nome_municipio) !== '') {
                $municipio = $this->resolverMunicipio($nome_municipio);

                if (! $municipio) {
                    return $this->municipioNaoEncontrado($nome_municipio);
                }

                $query->where('m.id', $municipio->id);
                $escopo = $municipio->nome;
            }

            $serie = $query->get();

            if ($serie->isEmpty()) {
                return json_encode(['encontrado' => false, 'escopo' => $escopo]);
            }

            return json_encode([
                'encontrado' => true,
                'escopo'     => $escopo,
                'serie'      => $serie->map(fn ($r) => ['ano' => (int) $r->ano, 'total' => (int) $r->total])->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('PaeAnalyticsTools::tendencia_anual_pae', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    private function protocolosPorMunicipio(): Builder
    {
        return DB::connection('pgsql_ai')
            ->table('pae_protocolos as pp')
            ->join('pae_empntos as pe', 'pe.id', '=', 'pp.pae_empnto_id')
            ->join('municipios as m', 'm.id', '=', 'pe.municipio_id')
            ->whereNull('pp.deleted_at')
            ->whereNull('pe.deleted_at');
    }
}
