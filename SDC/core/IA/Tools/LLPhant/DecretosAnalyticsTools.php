<?php

declare(strict_types=1);

namespace App\Core\IA\Tools\LLPhant;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DecretosAnalyticsTools
{
    use ResolvesMunicipio;

    /** Ano de referencia do processo: data do desastre, ou a de entrada quando ela falta. */
    private const ANO = 'EXTRACT(YEAR FROM COALESCE(p.data_ocorrencia_desastre, p.data_entrada, p.created_at))::int';

    /**
     * Ranking dos municipios mineiros com mais processos de decretacao (Situacao de Emergencia ou Calamidade Publica).
     *
     * @param int $ano opcional. Ano do desastre com 4 digitos (ex: 2025). Envie 0 para considerar todos os anos.
     * @param int $limite quantidade de municipios no ranking (padrao 10, maximo 50).
     */
    public function ranking_municipios_decretos(int $ano = 0, int $limite = 10): string
    {
        try {
            $query = $this->processosPorMunicipio()
                ->selectRaw('mu.nome AS municipio, COUNT(DISTINCT p.id) AS total, MAX(p.data_publicacao_mg) AS ultimo_decreto')
                ->groupBy('mu.nome')
                ->orderByRaw('COUNT(DISTINCT p.id) DESC')
                ->limit($this->limite($limite));

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
                    'posicao'        => $i + 1,
                    'municipio'      => $r->municipio,
                    'total'          => (int) $r->total,
                    'ultimo_decreto' => $r->ultimo_decreto,
                ])->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('DecretosAnalyticsTools::ranking_municipios_decretos', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Compara o historico de decretacoes de dois municipios mineiros: total de processos, reconhecimentos e tipos de desastre mais frequentes.
     *
     * @param string $municipio_a nome do primeiro municipio (ex: Januaria).
     * @param string $municipio_b nome do segundo municipio (ex: Montes Claros).
     */
    public function comparar_municipios_decretos(string $municipio_a, string $municipio_b): string
    {
        try {
            $resultado = [];

            foreach ([$municipio_a, $municipio_b] as $nome) {
                $municipio = $this->resolverMunicipio($nome);

                if (! $municipio) {
                    return $this->municipioNaoEncontrado($nome);
                }

                $resultado[] = $this->resumoMunicipio($municipio);
            }

            return json_encode(['encontrado' => true, 'comparacao' => $resultado]);
        } catch (\Exception $e) {
            Log::error('DecretosAnalyticsTools::comparar_municipios_decretos', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Quantidade de processos de decretacao por ano, no estado inteiro ou em um municipio.
     *
     * @param string $nome_municipio opcional. Nome do municipio (ex: Governador Valadares). Deixe vazio para o estado inteiro.
     * @param int $ano_inicio opcional. Primeiro ano da serie (ex: 2020). Envie 0 para toda a serie.
     */
    public function tendencia_anual_decretos(string $nome_municipio = '', int $ano_inicio = 0): string
    {
        try {
            $query = $this->processos()
                ->selectRaw(self::ANO . ' AS ano, COUNT(*) AS total')
                ->groupByRaw('1')
                ->orderByRaw('1');

            $escopo = 'Minas Gerais';

            if (trim($nome_municipio) !== '') {
                $municipio = $this->resolverMunicipio($nome_municipio);

                if (! $municipio) {
                    return $this->municipioNaoEncontrado($nome_municipio);
                }

                $query->whereExists(fn (Builder $q) => $q->selectRaw('1')
                    ->from('dec_decreto_municipios as dm')
                    ->whereColumn('dm.entrada_processos_id', 'p.id')
                    ->where('dm.municipio_id', $municipio->id)
                    ->whereNull('dm.deleted_at'));
                $escopo = $municipio->nome;
            }

            if ($ano_inicio > 0) {
                $query->whereRaw(self::ANO . ' >= ?', [$ano_inicio]);
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
            Log::error('DecretosAnalyticsTools::tendencia_anual_decretos', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Tipos de desastre (classificacao COBRADE) mais decretados, no estado ou em um municipio.
     *
     * @param string $nome_municipio opcional. Nome do municipio. Deixe vazio para o estado inteiro.
     * @param int $ano opcional. Ano do desastre com 4 digitos. Envie 0 para todos os anos.
     * @param int $limite quantidade de tipos no resultado (padrao 5, maximo 50).
     */
    public function cobrade_mais_frequente(string $nome_municipio = '', int $ano = 0, int $limite = 5): string
    {
        try {
            $query = $this->processos()
                ->selectRaw('p.cobrade, p.tipo_desastre, COUNT(*) AS total')
                ->groupBy('p.cobrade', 'p.tipo_desastre')
                ->orderByRaw('COUNT(*) DESC')
                ->limit($this->limite($limite));

            $escopo = 'Minas Gerais';

            if (trim($nome_municipio) !== '') {
                $municipio = $this->resolverMunicipio($nome_municipio);

                if (! $municipio) {
                    return $this->municipioNaoEncontrado($nome_municipio);
                }

                $query->whereExists(fn (Builder $q) => $q->selectRaw('1')
                    ->from('dec_decreto_municipios as dm')
                    ->whereColumn('dm.entrada_processos_id', 'p.id')
                    ->where('dm.municipio_id', $municipio->id)
                    ->whereNull('dm.deleted_at'));
                $escopo = $municipio->nome;
            }

            if ($ano > 0) {
                $query->whereRaw(self::ANO . ' = ?', [$ano]);
            }

            $tipos = $query->get();

            if ($tipos->isEmpty()) {
                return json_encode(['encontrado' => false, 'escopo' => $escopo, 'ano' => $ano ?: 'todos']);
            }

            return json_encode([
                'encontrado' => true,
                'escopo'     => $escopo,
                'ano'        => $ano ?: 'todos',
                'tipos'      => $tipos->map(fn ($r) => [
                    'cobrade'       => $r->cobrade,
                    'tipo_desastre' => $r->tipo_desastre,
                    'total'         => (int) $r->total,
                ])->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('DecretosAnalyticsTools::cobrade_mais_frequente', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    /**
     * Panorama das decretacoes em Minas Gerais: total de processos, municipios atingidos, distribuicao por status e por reconhecimento.
     *
     * @param int $ano opcional. Ano do desastre com 4 digitos. Envie 0 para todos os anos.
     */
    public function panorama_estado_decretos(int $ano = 0): string
    {
        try {
            $filtroAno = function (Builder $q) use ($ano): Builder {
                return $ano > 0 ? $q->whereRaw(self::ANO . ' = ?', [$ano]) : $q;
            };

            $total = $filtroAno($this->processos())->count();

            if ($total === 0) {
                return json_encode(['encontrado' => false, 'ano' => $ano ?: 'todos']);
            }

            $municipios = $filtroAno($this->processosPorMunicipio())->distinct()->count('dm.municipio_id');

            $porStatus = $filtroAno($this->processos())
                ->selectRaw("COALESCE(p.status, 'Sem status') AS status, COUNT(*) AS total")
                ->groupByRaw('1')
                ->orderByRaw('2 DESC')
                ->get();

            $porReconhecimento = $filtroAno($this->processos())
                ->selectRaw("COALESCE(p.reconhecimento, 'Nao informado') AS reconhecimento, COUNT(*) AS total")
                ->groupByRaw('1')
                ->orderByRaw('2 DESC')
                ->get();

            return json_encode([
                'encontrado'           => true,
                'ano'                  => $ano ?: 'todos',
                'total_processos'      => $total,
                'municipios_atingidos' => $municipios,
                'por_status'           => $porStatus->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total])->values()->toArray(),
                'por_reconhecimento'   => $porReconhecimento->map(fn ($r) => ['reconhecimento' => $r->reconhecimento, 'total' => (int) $r->total])->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('DecretosAnalyticsTools::panorama_estado_decretos', ['error' => $e->getMessage()]);
            return json_encode(['encontrado' => false, 'erro' => 'Falha ao consultar banco de dados']);
        }
    }

    private function resumoMunicipio(object $municipio): array
    {
        $base = fn () => $this->processosPorMunicipio()->where('dm.municipio_id', $municipio->id);

        $tipos = $base()
            ->selectRaw('p.tipo_desastre, COUNT(DISTINCT p.id) AS total')
            ->groupBy('p.tipo_desastre')
            ->orderByRaw('COUNT(DISTINCT p.id) DESC')
            ->limit(3)
            ->get();

        return [
            'municipio'          => $municipio->nome,
            'mesorregiao'        => $municipio->mesorregiao,
            'total_processos'    => $base()->distinct()->count('p.id'),
            'ultimo_decreto'     => $base()->max('p.data_publicacao_mg'),
            'reconhecidos'       => $base()->whereNotNull('p.reconhecimento')->distinct()->count('p.id'),
            'principais_tipos'   => $tipos->map(fn ($r) => ['tipo_desastre' => $r->tipo_desastre, 'total' => (int) $r->total])->values()->toArray(),
        ];
    }

    private function processos(): Builder
    {
        return DB::connection('pgsql_ai')
            ->table('dec_entrada_processos as p')
            ->whereNull('p.deleted_at');
    }

    private function processosPorMunicipio(): Builder
    {
        return $this->processos()
            ->join('dec_decreto_municipios as dm', 'dm.entrada_processos_id', '=', 'p.id')
            ->join('municipios as mu', 'mu.id', '=', 'dm.municipio_id')
            ->whereNull('dm.deleted_at');
    }

    private function limite(int $limite): int
    {
        return max(1, min($limite, 50));
    }
}
