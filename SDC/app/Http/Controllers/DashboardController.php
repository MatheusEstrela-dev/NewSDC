<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Dashboard\Services\DashboardStatisticsService;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Queries\DemandaDashboardQuery;
use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Services\LeaderboardQuery;
use App\Modules\Ranking\Services\RankingReadService;
use App\Modules\Ranking\Support\RankingAccess;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatisticsService $dashboardStats,
        private readonly DemandaDashboardQuery $demandaDashboardQuery,
    ) {}

    public function index(Request $request): Response
    {
        $stats = $this->dashboardStats->getStats();

        return Inertia::render('Dashboard', array_merge($stats->toArray(), [
            // FORA do DTO de proposito: o DTO e cacheado por
            // Cache::flexible('dashboard.stats.full') e vale para todo mundo,
            // enquanto permissao e por usuario. Cachear a flag entregaria o
            // link da frota ao primeiro visitante que tivesse a permissao e
            // depois a todos os outros.
            'canVerFrota' => (bool) $request->user()?->can('plantao.viaturas.view'),

            // Mesmo motivo: e o saldo DESTE usuario. No DTO cacheado, o
            // primeiro a abrir o dashboard entregaria o proprio placar a todos
            // os seguintes.
            'rankingResumo' => $this->resumoDoRanking($request),

            // Mesmo motivo: escopoVisivel() decide minhas-vs-todas por usuario
            // (via permissao demandas.chamados.manage).
            'demandasRecentes' => $this->demandasRecentesParaWidget($request),
        ]));
    }

    /**
     * Resumo pessoal para o widget do dashboard, ou null.
     *
     * Devolve null - e o widget mostra estado vazio em vez de zeros que
     * pareceriam reais - quando o modulo esta desligado ou em sombra, quando o
     * usuario nao pode ver o placar, ou quando a leitura falha.
     *
     * O catch e proposital e largo: a database do ranking e independente e pode
     * nao existir, estar sem as tabelas ou sem credencial no ambiente. Nada
     * disso pode derrubar o dashboard inteiro, que e a porta de entrada do
     * sistema e nao depende do ranking para nada.
     *
     * @return array{pontos: int, posicao: ?int, faixa: string, atualizado_em: ?string}|null
     */
    private function resumoDoRanking(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null || ! RankingAccess::visivel($user)) {
            return null;
        }

        try {
            $filtro = new FiltroPlacar(
                escopo: EscopoPlacar::Usuario,
                periodoChave: TipoPeriodo::Trimestre->chave(new DateTimeImmutable()),
                modulo: 'all',
                geracao: app(RankingReadService::class)->geracao(),
            );

            return app(RankingReadService::class)->resumo(
                (int) $user->getKey(),
                $filtro,
                app(LeaderboardQuery::class),
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Demandas recentes do usuario, para o widget da Visao Geral.
     *
     * Reusa a mesma DemandaDashboardQuery::recentes() do painel de demandas
     * (Task 18), ja com o limite padrao de 5 e o escopo minhas-vs-todas via
     * escopoVisivel(). O catch e largo pelo mesmo motivo do resumoDoRanking:
     * um modulo indisponivel no ambiente nao pode derrubar a Visao Geral.
     *
     * @return list<array{id:int, protocolo:string, titulo:string, etapa:string, etapa_label:string, prioridade_simples:string, prioridade_label:string, solicitante:?string, created_at:?string}>
     */
    private function demandasRecentesParaWidget(Request $request): array
    {
        $user = $request->user();

        if ($user === null || ! $user->can('demandas.chamados.view') || ! Schema::hasTable((new Demanda())->getTable())) {
            return [];
        }

        try {
            $demandas = $this->demandaDashboardQuery->recentes(
                (int) $user->getKey(),
                $user->can('demandas.chamados.manage'),
            );

            return array_map(static fn (Demanda $d): array => [
                'id' => $d->id,
                'protocolo' => $d->protocolo,
                'titulo' => $d->titulo,
                'etapa' => $d->status->etapa()->value,
                'etapa_label' => $d->status->etapa()->label(),
                'prioridade_simples' => PrioridadeSimples::dePrioridade($d->prioridade)->value,
                'prioridade_label' => PrioridadeSimples::dePrioridade($d->prioridade)->label(),
                'solicitante' => $d->solicitante?->name,
                'created_at' => $d->created_at?->toIso8601String(),
            ], $demandas);
        } catch (Throwable) {
            return [];
        }
    }
}
