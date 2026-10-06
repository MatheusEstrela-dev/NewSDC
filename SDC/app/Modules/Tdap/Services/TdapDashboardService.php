<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\Enums\PeriodoEntregas;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Historico;
use App\Modules\Tdap\Models\Prestador;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Indicadores do dashboard do TDAP.
 *
 * Escopo: todo metodo recebe o municipio de lotacao de quem olha (null =
 * estadual), o mesmo recorte da fila de validacao -- a permissao diz o que a
 * pessoa pode fazer, o escopo diz sobre o que. O cadastro de prestadores e
 * estadual e nao entra no recorte.
 *
 * Criterios compartilhados com as telas, para o card e a tela de destino
 * mostrarem o mesmo numero:
 *  - entrega = CronoViagem::entregue() (aprovada, de alocacao e cronograma nao
 *    excluidos), volume = capacidade do caminhao, como recalcularEntregas;
 *  - cronogramas e volumes (ativo/entregue) = CronogramaService::obterEstatisticas
 *    (cards da tela Cronogramas);
 *  - fila de validacao = CronoViagemService::filaDeValidacao (tela Pendentes);
 *  - prestadores ativos = PrestadorService::obterEstatisticas (tela Prestadores).
 */
class TdapDashboardService
{
    /** Regioes nomeadas no grafico de cobertura; o resto vira "Outras regioes". */
    private const REGIOES_EM_DESTAQUE = 3;

    private const SEM_REGIAO = 'Sem região';

    public function __construct(
        private readonly CronoViagemService $viagens,
        private readonly PrestadorService $prestadores,
        private readonly CronogramaService $cronogramasService,
    ) {}

    /**
     * @return array<string, int|float>
     */
    public function kpis(?int $municipioId): array
    {
        // Mesma conta dos cards da tela de Cronogramas (contagens e volumes).
        $cronogramas = $this->cronogramasService->obterEstatisticas($municipioId);

        // Mes da VIAGEM (data_registro), como o grafico de entregas: pela data
        // de aprovacao, uma validacao em lote jogava a agua de outubro em novembro.
        $doMes = $this->viagensEntregues($municipioId)->where('data_registro', '>=', CarbonImmutable::now()->startOfMonth());

        $prestadoresEmOperacao = Prestador::query()
            ->ativo()
            ->whereIn('id', $this->cronogramas($municipioId)->ativo()->select('prestador_id'))
            ->count();

        return [
            'cronogramas_ativos'              => $cronogramas['ativos'],
            'cronogramas_encerrados'          => $cronogramas['encerrados'],
            'cronogramas_rascunhos'           => $cronogramas['rascunhos'],
            'volume_ativo_m3'                 => $cronogramas['volume_ativo_m3'],
            'volume_entregue_m3'              => $cronogramas['volume_entregue_m3'],
            'm3_entregues_mes'                => $this->somarM3($doMes),
            'prestadores_ativos'              => $this->prestadores->obterEstatisticas()['ativos'],
            'prestadores_em_operacao'         => $prestadoresEmOperacao,
            'viagens_pendentes_validar'       => $this->viagens->filaDeValidacao($municipioId)->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function eventosRecentes(int $limite): array
    {
        return Historico::query()
            ->with('user:id,name')
            ->orderByDesc('data_evento')
            ->limit($limite)
            ->get()
            ->map(fn ($h) => [
                'id'           => $h->id,
                'data_evento'  => $h->data_evento?->toIso8601String(),
                'tipo_evento'  => $h->tipo_evento,
                'entity_type'  => $h->entity_type,
                'entity_id'    => $h->entity_id,
                'obs'          => $h->obs,
                'user_name'    => $h->user?->name,
            ])
            ->toArray();
    }

    /**
     * Cronogramas ativos mais recentes, com a execucao em viagens para a barra
     * de progresso (mesmos campos de CronogramaIndexResource).
     *
     * @return array<int, array<string, mixed>>
     */
    public function cronogramasAtivos(?int $municipioId, int $limite): array
    {
        return $this->cronogramas($municipioId)
            ->ativo()
            ->with(['municipio:id,nome,uf', 'prestador:id,nome'])
            ->withCount(['caminhoes'])
            ->comExecucaoDeViagens()
            ->orderByDesc('dt_inicio')
            ->limit($limite)
            ->get()
            ->map(fn (Cronograma $c) => [
                'id'                 => $c->id,
                'numero'             => $c->numero,
                'dt_inicio'          => $c->dt_inicio?->toDateString(),
                'dt_final'           => $c->dt_final?->toDateString(),
                'municipio_nome'     => $c->municipio?->nome,
                'municipio_uf'       => $c->municipio?->uf,
                'prestador_nome'     => $c->prestador?->nome,
                'caminhoes_count'    => (int) $c->caminhoes_count,
                'viagens_previstas'  => $c->viagens_previstas,
                'viagens_realizadas' => $c->viagens_realizadas,
                'dias_restantes'     => $c->dias_restantes,
            ])
            ->toArray();
    }

    /**
     * Viagens entregues por dia/mes da janela, mais o total da janela anterior
     * de mesmo comprimento para a variacao.
     *
     * A data e a da viagem (`data_registro`), nao a da validacao: o grafico
     * mostra quando a agua chegou, e uma aprovacao em lote no fim do mes nao
     * pode empilhar o mes inteiro num dia so.
     *
     * @return array{periodo: string, opcoes: array<int, array{value: string, label: string}>, granularidade: string, total: int, total_anterior: int, variacao: float|null, serie: array<int, array{data: string, total: int}>}
     */
    public function entregas(?int $municipioId, PeriodoEntregas $periodo): array
    {
        $agora = CarbonImmutable::now();
        $inicio = $periodo->inicio($agora);
        $granularidade = $periodo->granularidade();

        // to_char devolve a chave ja no formato do bucket montado abaixo; a
        // sessao do Postgres roda no fuso da aplicacao (config/database.php).
        $porBucket = $this->viagensEntregues($municipioId)
            ->whereBetween('data_registro', [$inicio, $agora])
            ->selectRaw("to_char(date_trunc('{$granularidade}', data_registro), 'YYYY-MM-DD') AS bucket, COUNT(*) AS total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $serie = collect(CarbonPeriod::create($inicio, "1 {$granularidade}", $agora))
            ->map(fn ($data) => [
                'data'  => $data->toDateString(),
                'total' => (int) ($porBucket[$data->toDateString()] ?? 0),
            ])
            ->values()
            ->all();

        $total = array_sum(array_column($serie, 'total'));
        $totalAnterior = $this->viagensEntregues($municipioId)
            ->whereBetween('data_registro', [$periodo->recuar($inicio), $periodo->recuar($agora)])
            ->count();

        return [
            'periodo'        => $periodo->value,
            'opcoes'         => PeriodoEntregas::options(),
            'granularidade'  => $granularidade,
            'total'          => $total,
            'total_anterior' => $totalAnterior,
            // Sem base de comparacao nao ha variacao: "+100%" sobre zero mentiria.
            'variacao'       => $totalAnterior > 0 ? round(($total - $totalAnterior) / $totalAnterior * 100, 1) : null,
            'serie'          => $serie,
        ];
    }

    /**
     * Cobertura dos cronogramas ativos por mesorregiao do municipio atendido.
     *
     * A participacao de cada regiao e sobre as viagens PREVISTAS (o que esta
     * programado), e a execucao soma as realizadas limitadas as previstas de
     * cada cronograma -- o mesmo teto da barra de CronogramaViagensBar, para
     * um cronograma que passou do previsto nao mascarar outro atrasado.
     *
     * @return array<string, mixed>
     */
    public function cobertura(?int $municipioId): array
    {
        // select antes dos agregados: depois deles o get(colunas) seria ignorado.
        $cronogramas = $this->cronogramas($municipioId)
            ->select(['tdap_cronogramas.id', 'tdap_cronogramas.municipio_id'])
            ->ativo()
            ->with('municipio:id,mesorregiao')
            ->comExecucaoDeViagens()
            ->get();

        $previstas = $cronogramas->sum(fn (Cronograma $c) => $c->viagens_previstas);
        $realizadas = $cronogramas->sum(fn (Cronograma $c) => min($c->viagens_realizadas, $c->viagens_previstas));

        return [
            'municipios'               => $cronogramas->pluck('municipio_id')->unique()->count(),
            'municipios_atendidos_mes' => $this->municipiosAtendidosNoMes($municipioId),
            'viagens_previstas'        => $previstas,
            'viagens_realizadas'       => $realizadas,
            'percentual_execucao'      => $previstas > 0 ? (int) round($realizadas / $previstas * 100) : null,
            'regioes'                  => $this->participacaoPorRegiao($cronogramas, $previstas),
        ];
    }

    /**
     * Base de todo indicador de cronograma: sem os arquivados, como a listagem
     * de Cronogramas -- o card e a lista para onde ele leva contam igual.
     */
    private function cronogramas(?int $municipioId): Builder
    {
        return Cronograma::query()
            ->naoArquivado()
            ->when($municipioId !== null, fn (Builder $q) => $q->doMunicipio($municipioId));
    }

    private function viagensEntregues(?int $municipioId): Builder
    {
        return CronoViagem::query()
            ->entregue()
            ->when($municipioId !== null, fn (Builder $q) => $q->doMunicipio($municipioId));
    }

    /**
     * m3 das viagens: capacidade do caminhao por viagem, a mesma conta de
     * CronoCaminhaoService::recalcularEntregas. Caminhao excluido depois NAO
     * zera a agua que ele ja levou -- por isso o join nao filtra deleted_at.
     */
    private function somarM3(Builder $viagens): float
    {
        return round((float) $viagens
            ->join('tdap_crono_caminhoes as cc_m3', 'cc_m3.id', '=', 'tdap_crono_viagens.crono_caminhao_id')
            ->join('tdap_caminhoes as caminhao_m3', 'caminhao_m3.id', '=', 'cc_m3.caminhao_id')
            ->sum('caminhao_m3.capacidade_m3'), 2);
    }

    /** Municipios com cronograma ativo que receberam ao menos uma viagem neste mes. */
    private function municipiosAtendidosNoMes(?int $municipioId): int
    {
        $agora = CarbonImmutable::now();

        return $this->cronogramas($municipioId)
            ->ativo()
            ->whereHas('viagens', fn ($q) => $q
                ->aprovada()
                ->whereBetween('data_registro', [$agora->startOfMonth(), $agora]))
            ->distinct()
            ->count('municipio_id');
    }

    /**
     * @param  Collection<int, Cronograma>  $cronogramas
     * @return array<int, array{regiao: string, municipios: int, viagens_previstas: int, percentual: int}>
     */
    private function participacaoPorRegiao(Collection $cronogramas, int $previstasTotal): array
    {
        if ($previstasTotal === 0) {
            return [];
        }

        $regioes = $cronogramas
            ->groupBy(fn (Cronograma $c) => $c->municipio?->mesorregiao ?: self::SEM_REGIAO)
            ->map(fn (Collection $grupo, string $regiao) => [
                'regiao'            => $regiao,
                'municipios'        => $grupo->pluck('municipio_id')->unique()->count(),
                'viagens_previstas' => $grupo->sum(fn (Cronograma $c) => $c->viagens_previstas),
            ])
            ->filter(fn (array $r) => $r['viagens_previstas'] > 0)
            ->sortByDesc('viagens_previstas')
            ->values();

        if ($regioes->count() > self::REGIOES_EM_DESTAQUE + 1) {
            $outras = $regioes->slice(self::REGIOES_EM_DESTAQUE);
            $regioes = $regioes->take(self::REGIOES_EM_DESTAQUE)->push([
                'regiao'            => 'Outras regiões',
                'municipios'        => $outras->sum('municipios'),
                'viagens_previstas' => $outras->sum('viagens_previstas'),
            ]);
        }

        $percentuais = $this->percentuaisQueSomamCem($regioes->pluck('viagens_previstas')->all());

        return $regioes
            ->map(fn (array $r, int $i) => $r + ['percentual' => $percentuais[$i]])
            ->all();
    }

    /**
     * Percentuais inteiros que fecham exatamente 100 (maiores restos): o
     * arredondamento simples de 3 fatias de 33,3% mostraria 99% na legenda.
     *
     * @param  array<int, int>  $valores
     * @return array<int, int>
     */
    private function percentuaisQueSomamCem(array $valores): array
    {
        $total = array_sum($valores);
        $exatos = array_map(fn (int $v) => $v / $total * 100, $valores);
        $inteiros = array_map(fn (float $p) => (int) floor($p), $exatos);

        $restos = [];
        foreach ($exatos as $i => $p) {
            $restos[$i] = $p - $inteiros[$i];
        }
        arsort($restos);

        foreach (array_slice(array_keys($restos), 0, 100 - array_sum($inteiros)) as $i) {
            $inteiros[$i]++;
        }

        return $inteiros;
    }
}
