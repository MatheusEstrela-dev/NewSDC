<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Dashboard\Services\DashboardStatisticsService;
use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Services\LeaderboardQuery;
use App\Modules\Ranking\Services\RankingReadService;
use App\Modules\Ranking\Support\RankingAccess;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardStatisticsService $dashboardStats) {}

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
                periodoChave: TipoPeriodo::Mes->chave(new DateTimeImmutable()),
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
}
