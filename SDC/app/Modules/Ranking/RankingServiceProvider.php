<?php

declare(strict_types=1);

namespace App\Modules\Ranking;

use App\Modules\Ranking\Services\InstitutionalContextResolver;
use App\Modules\Ranking\Services\LeaderboardQuery;
use App\Modules\Ranking\Services\RecordScoreTransaction;
use App\Modules\Ranking\Services\ReverseScoreEntry;
use App\Modules\Ranking\Services\ScoreCalculator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/**
 * Ranking - pontos por entrega de negocio, placar e faixas.
 *
 * Plano: docs/superpowers/plans/2026-09-21-ranqueamento-ipcm.md
 *
 * Arquitetura:
 *   - Schema Postgres proprio (`ranking`), na mesma conexao do SDC.
 *   - Consome Domain Events dos modulos de origem via listeners idempotentes
 *     (App\Core\Events\IdempotentListener), nao por polling.
 *   - Livro de pontos append-only; placar e projecao de leitura reconstruivel.
 *
 * CUIDADO COM OCTANE: os singletons registrados aqui sobrevivem entre requests
 * do worker. Nenhum service deste modulo pode guardar estado de request -
 * usuario, orgao ativo, periodo selecionado. O contexto institucional e sempre
 * recebido por argumento, nunca lido de Auth dentro do service; caso contrario
 * o contexto de um usuario vaza na pontuacao do proximo. Ver a lista 'flush'
 * em config/octane.php, que existe por causa desse tipo de vazamento.
 */
class RankingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Todos stateless, pelo motivo descrito no docblock da classe.

        // Percentual e teto entram por construtor em vez de config() em tempo
        // de calculo: o singleton sobrevive entre requests e o calculo precisa
        // ser reproduzivel em teste unitario, sem bootar o framework.
        $this->app->singleton(ScoreCalculator::class, fn () => new ScoreCalculator(
            (int) config('ranking.pontuacao.bonus_percentual', 20),
            (int) config('ranking.pontuacao.teto_por_lancamento', 500),
        ));

        $this->app->singleton(InstitutionalContextResolver::class, fn () => new InstitutionalContextResolver(
            (string) config('ranking.conexao', 'ranking'),
        ));

        $this->app->singleton(RecordScoreTransaction::class);
        $this->app->singleton(ReverseScoreEntry::class);

        // Leitura do placar usa a conexao de SELECT, separada da de escrita.
        $this->app->singleton(LeaderboardQuery::class, fn ($app) => new LeaderboardQuery(
            DB::connection((string) config('ranking.conexao_leitura', 'ranking_read')),
            $app->make(CacheRepository::class),
            (int) config('ranking.placar.cache_segundos', 60),
        ));
    }

    public function boot(): void
    {
        // Os listeners dos eventos de RAT e PAE sao ligados na Fase 4, e apenas
        // quando config('ranking.habilitado') estiver ativo. Registrar listener
        // com o modulo desligado faria o Ranking consumir evento e gravar livro
        // sem que o placar exista.
    }
}
