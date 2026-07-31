<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Filters\ProcessoFilter;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use App\Support\Concurrency\Concurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Service responsible for Processo statistics and dashboard metrics.
 * Cache: 30 minutos para estatisticas de dashboard (quando sem filtros).
 *
 * Calcula estatisticas para os 5 cards do dashboard de Decretacoes:
 * - Total de Eventos
 * - Registros
 * - Decretacoes
 * - Municipios Atingidos
 * - Decretacoes Vigentes
 *
 * Cada card tem breakdown por tipo de desastre (ECP/SE).
 */
class ProcessoStatsService
{
    private const CACHE_PREFIX = 'decretacoes.stats.';

    /**
     * Get dashboard statistics for DecretacoesStatsCards component.
     * Cached for 30 minutes if no active filters.
     *
     * Retorna estrutura compativel com o componente Vue. Cada metrica tem
     * breakdown nas tres classes de situacao_anormalidade (ECP/SE/N1) mais a
     * leitura em grao municipio (PorMunicipio, null quando nao se aplica):
     * - totalEventos, totalEventosEcp, totalEventosSe, totalEventosN1, totalEventosPorMunicipio
     * - registros, registrosEcp, registrosSe, registrosN1, registrosPorMunicipio
     * - decretacoes, decretacoesEcp, decretacoesSe, decretacoesN1, decretacoesPorMunicipio
     * - municipiosAtingidos, municipiosAtingidosEcp, municipiosAtingidosSe, municipiosAtingidosN1
     * - decretacoesVigentes, decretacoesVigentesEcp, decretacoesVigentesSe, decretacoesVigentesN1, decretacoesVigentesPorMunicipio
     *
     * @param array $filters Filtros opcionais aplicados na interface
     * @return array<string, int|null>
     */
    public function getDashboardStatistics(array $filters = []): array
    {
        // Cada familia de card (4 counts) vira uma task independente: paralelas
        // nos task workers do Swoole, sequenciais no fallback (mesmo resultado).
        // As closures sao montadas em foreach (nunca aninhadas na mesma
        // expressao): a serializacao extrai o fonte pela posicao no arquivo e
        // closures aninhadas na mesma linha serializam a closure errada.
        $calculaEstatisticas = function () use ($filters) {
            $familias = [
                'totalEventos',
                'registros',
                'decretacoes',
                'municipiosAtingidos',
                'decretacoesVigentes',
            ];

            $closures = [];
            foreach ($familias as $familia) {
                $closures[$familia] = static function () use ($familia, $filters) {
                    return app(ProcessoStatsService::class)->computeFamilia($familia, $filters);
                };
            }

            $resultados = Concurrency::tasks($closures);

            return array_merge(...array_values($resultados));
        };

        // Verifica se existem filtros "ativos" (ignorando campos vazios/nulos)
        $hasFilters = collect($filters)
            ->only([
                'search', 'data_entrada', 'data_entrada_inicio', 'data_entrada_fim',
                'processo', 'reconhecimento', 'analista', 'situacao_anormalidade',
                'data_decreto_inicio', 'data_decreto_fim', 'vigencia_status',
                'tipo_desastre_id', 'municipio_id', 'n_protocolo_fide',
                'tipo_lancamento'
            ])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->isNotEmpty();

        // Sem filtros ativos: cacheia por 30 minutos. TTL (em vez de rememberForever)
        // garante auto-recuperacao apos cargas que nao passam pelo Eloquent/Observer
        // (ex.: import SQL bruto), que de outra forma deixariam a estatistica presa.
        // O sufixo versionado aposenta a chave anterior sempre que a regra de
        // calculo muda: sem isso a correcao ficaria invisivel por 30 minutos.
        // v3 = Decretacoes passou a incluir reconhecimento NULL.
        // v4 = Vigencia passou a depender so do prazo, sem whitelist de status.
        if (!$hasFilters) {
            return Cache::remember(self::CACHE_PREFIX . 'dashboard.v4', now()->addMinutes(30), $calculaEstatisticas);
        }

        // Se houver filtros, recalcula on the fly sem tocar no cache
        return $calculaEstatisticas();
    }

    /**
     * Calcula uma familia de estatisticas (total + ECP/SE/N1) a partir dos
     * filtros crus. Publico e auto-contido de proposito: e o metodo que as
     * tasks do Concurrency::tasks() resolvem em outro processo, entao recebe
     * apenas escalares/arrays e reconstroi a query localmente.
     *
     * @param array $filters Mesmos filtros crus aceitos por getDashboardStatistics
     * @return array<string, int|null>
     */
    public function computeFamilia(string $familia, array $filters = []): array
    {
        $metodo = match ($familia) {
            'totalEventos'        => 'getTotalEventos',
            'registros'           => 'getRegistros',
            'decretacoes'         => 'getDecretacoes',
            'municipiosAtingidos' => 'getMunicipiosAtingidos',
            'decretacoesVigentes' => 'getDecretacoesVigentes',
            default => throw new InvalidArgumentException("Familia de estatistica desconhecida: {$familia}"),
        };

        $baseQuery = $this->buildBaseQuery($filters);

        return [
            $familia                  => $this->{$metodo}($baseQuery),
            $familia . 'Ecp'          => $this->{$metodo}($baseQuery, 'ECP'),
            $familia . 'Se'           => $this->{$metodo}($baseQuery, 'SE'),
            $familia . 'N1'           => $this->{$metodo}($baseQuery, 'N1'),
            $familia . 'PorMunicipio' => $this->getPorMunicipio($familia, $baseQuery),
        ];
    }

    /**
     * Conta a familia no grao municipio (processo x municipio) em vez de por
     * processo. Um processo que cobre N municipios rende N linhas.
     *
     * Conta os vinculos direto em dec_decreto_municipios, SEM juntar com
     * municipios. O join por municipios.id descarta 575 dos 647 vinculos
     * (dec_decreto_municipios.municipio_id guarda id CEDEC legado, que nao
     * corresponde a municipios.id; a ponte real e por codigo_ibge). Juntar
     * aqui faria o card exibir 72 em vez de 647.
     *
     * Retorna null para municipiosAtingidos, que ja e medido em grao
     * municipio e portanto nao tem leitura secundaria.
     */
    private function getPorMunicipio(string $familia, Builder $baseQuery): ?int
    {
        if ($familia === 'municipiosAtingidos') {
            return null;
        }

        $query = clone $baseQuery;

        match ($familia) {
            'registros'           => $query->where('reconhecimento', 'Registro'),
            'decretacoes'         => $this->applyDecretacaoConstraints($query),
            'decretacoesVigentes' => $this->applyVigentesConstraints($query),
            default               => $query,
        };

        // Subquery em vez de pluck(): evita a ida extra ao banco e nao trafega
        // a lista de ids de volta para o PHP.
        return DecretoMunicipio::whereIn('entrada_processos_id', $query->select('id'))->count();
    }

    /**
     * Query base de Processo com os filtros da interface aplicados (padrao
     * ProcessoFilter sobre um Request reconstruido dos filtros crus).
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $baseQuery = Processo::query();

        if (!empty($filters)) {
            $request = new Request($filters);
            $filter = new ProcessoFilter($request);
            $baseQuery = $filter->apply($baseQuery);
        }

        return $baseQuery;
    }

    /**
     * Get basic statistics (legacy method).
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total'      => Processo::count(),
            'em_analise' => Processo::where('reconhecimento', 'Registro')->count(),
            'aprovados'  => Processo::where('reconhecimento', 'like', 'Reconhecido%')->count(),
            'rejeitados' => Processo::where('reconhecimento', 'Nao reconhecido')->count(),
        ];
    }

    /**
     * Total de Eventos: conta todos os processos.
     */
    private function getTotalEventos(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = clone $baseQuery;

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return $query->count();
    }

    /**
     * Registros: conta processos com reconhecimento = 'Registro'.
     */
    private function getRegistros(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = clone $baseQuery;
        $query->where('reconhecimento', 'Registro');

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return $query->count();
    }

    /**
     * Decretacao e todo processo que nao e Registro, inclusive os de
     * reconhecimento nulo.
     *
     * Isolada porque a regra vale em tres leituras (contagem por processo,
     * municipios atingidos e contagem por municipio) e precisa ser a mesma nas
     * tres. So `!= 'Registro'` deixava de fora quem tem reconhecimento NULL:
     * a comparacao avalia NULL, nao TRUE, entao esses processos entravam no
     * Total de Eventos e em nenhum dos dois cards, quebrando a identidade
     * Total = Registros + Decretacoes.
     */
    private function applyDecretacaoConstraints(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('reconhecimento', '!=', 'Registro')
              ->orWhereNull('reconhecimento');
        });
    }

    /**
     * Decretacoes: conta processos que nao sao Registro.
     */
    private function getDecretacoes(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = $this->applyDecretacaoConstraints(clone $baseQuery);

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return $query->count();
    }

    /**
     * Municipios Atingidos: conta municipios distintos com decretacoes.
     */
    private function getMunicipiosAtingidos(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = $this->applyDecretacaoConstraints(clone $baseQuery);

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return DecretoMunicipio::whereIn('entrada_processos_id', $query->select('id'))
            ->distinct('municipio_id')
            ->count('municipio_id');
    }

    /**
     * Decretacoes Vigentes: conta processos vigentes reconhecidos pelo estado.
     *
     * Vigente = data_publicacao_mg NULL ou data_publicacao_mg + prazo_vigencia >= hoje
     * + reconhecimento LIKE 'Reconhecido pelo Estado%'
     */
    private function getDecretacoesVigentes(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = $this->applyVigentesConstraints(clone $baseQuery);

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return $query->count();
    }

    /**
     * Regra de vigencia isolada para ser reaproveitada pela contagem por
     * processo e pela contagem por municipio.
     *
     * Vigente = dentro do PRAZO e sendo decretacao. A vigencia depende do
     * prazo do decreto, nao do estagio de reconhecimento: um decreto em
     * analise pelo Estado esta em vigor do mesmo jeito.
     *
     * A whitelist anterior ('Reconhecido pelo Estado%' e as variantes
     * 'somente pela uniao') excluia processos ainda dentro do prazo apenas
     * por causa do estagio em que estavam. No legado essa mesma correcao
     * moveu o card de 152 para 167.
     *
     * Fica em aberto no legado, e igualmente aqui: 'Nao reconhecido pelo
     * Estado e Uniao' passa a contar como vigente, o que e discutivel.
     */
    private function applyVigentesConstraints(Builder $query): Builder
    {
        $query->where(function ($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) >= CURRENT_DATE");
        });

        return $this->applyDecretacaoConstraints($query);
    }

    /**
     * Get the count of valid (vigentes) processos.
     *
     * @return int
     */
    public function getVigentesCount(): int
    {
        return Cache::rememberForever(self::CACHE_PREFIX . 'vigentes', function () {
            return Processo::where(function ($q) {
                $q->whereNull('data_publicacao_mg')
                  ->orWhereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) >= CURRENT_DATE");
            })->count();
        });
    }

    /** Limpa todo o cache de estatisticas de Decretacoes (inclui as chaves legadas). */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'dashboard.v4');
        Cache::forget(self::CACHE_PREFIX . 'dashboard.v3');
        Cache::forget(self::CACHE_PREFIX . 'dashboard.v2');
        Cache::forget(self::CACHE_PREFIX . 'dashboard');
        Cache::forget(self::CACHE_PREFIX . 'vigentes');
    }
}
