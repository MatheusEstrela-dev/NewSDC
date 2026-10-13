<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RatAnalyticsTools
{
    use ResolvesMunicipio;

    /** Ano do fato; RAT sem data do fato conta pelo ano de criacao. */
    private const ANO = 'EXTRACT(YEAR FROM COALESCE(dg.data_fato, o.created_at))::int';

    /**
     * Ranking dos municipios mineiros com mais RATs (Registro de Atendimento Tecnico) registrados.
     *
     * @param int $ano opcional. Ano do fato com 4 digitos (ex: 2025). Envie 0 para considerar todos os anos.
     * @param int $limite quantidade de municipios no ranking (padrao 10, maximo 50).
     */
    public function ranking_municipios_rat(int $ano = 0, int $limite = 10): string
    {
        try {
            $query = $this->ratsComDadosGerais()
                ->whereNotNull('dg.local_municipio_nome')
                ->selectRaw('dg.local_municipio_nome AS municipio, COUNT(DISTINCT o.id) AS total, SUM(CASE WHEN o.status = 1 THEN 1 ELSE 0 END) AS finalizados')
                ->groupBy('dg.local_municipio_nome')
                ->orderByRaw('COUNT(DISTINCT o.id) DESC')
                ->limit(max(1, min($limite, 50)));

            if ($ano > 0) {
                $query->whereRaw(self::ANO . ' = ?', [$ano]);
            }

            $ranking = $query->get();

            if ($ranking->isEmpty()) {
                return json_encode(['encontrado' => false, 'ano' => $ano ?: 'todos']);
            }

            return json_encode([
                'encontrado' => true,
                'ano'        => $ano ?: 'todos',
                'ranking'    => $ranking->map(fn ($r, $i) => [
                    'posicao'     => $i + 1,
                    'municipio'   => $r->municipio,
                    'total'       => (int) $r->total,
                    'finalizados' => (int) $r->finalizados,
                ])->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('RatAnalyticsTools::ranking_municipios_rat', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Quantidade de RATs por ano, no estado inteiro ou em um municipio.
     *
     * @param string $nome_municipio opcional. Nome do municipio (ex: Juiz de Fora). Deixe vazio para o estado inteiro.
     */
    public function tendencia_anual_rat(string $nome_municipio = ''): string
    {
        try {
            $query = $this->ratsComDadosGerais()
                ->selectRaw(self::ANO . ' AS ano, COUNT(DISTINCT o.id) AS total')
                ->groupByRaw('1')
                ->orderByRaw('1');

            $escopo = 'Minas Gerais';

            if (trim($nome_municipio) !== '') {
                $municipio = $this->resolverMunicipio($nome_municipio);

                if (! $municipio) {
                    return $this->municipioNaoEncontrado($nome_municipio);
                }

                $query->whereRaw('LOWER(dg.local_municipio_nome) = LOWER(?)', [$municipio->nome]);
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
            Log::error('RatAnalyticsTools::tendencia_anual_rat', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    private function ratsComDadosGerais(): Builder
    {
        return DB::connection('pgsql_ai')
            ->table('rat_ocorrencias as o')
            ->join('rat_relato_dados_gerais as dg', 'dg.ocorrencia_id', '=', 'o.id')
            ->whereNull('o.deleted_at')
            ->whereNull('dg.deleted_at');
    }
}
