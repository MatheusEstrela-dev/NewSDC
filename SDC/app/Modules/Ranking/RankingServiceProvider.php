<?php

declare(strict_types=1);

namespace App\Modules\Ranking;

use App\Modules\Pae\Domain\Events\FormularioValidadoV1;
use App\Modules\Pae\Domain\Events\ParecerConcluidoV1;
use App\Modules\Pae\Domain\Events\ProtocoloEnviadoV1;
use App\Modules\Pae\Domain\Events\RevisaoAceitaV1;
use App\Modules\Rat\Domain\Events\RegistroCompletoV1;
use App\Modules\Rat\Domain\Events\RelatorioFinalizadoV1;
use App\Modules\Rat\Domain\Events\VistoriaValidadaV1;
use App\Modules\Ranking\Adapters\PaeAdapter;
use App\Modules\Ranking\Adapters\RatAdapter;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\Listeners\PontuarFatoDeNegocio;
use App\Modules\Ranking\Services\ConfirmScoreTransaction;
use App\Modules\Ranking\Services\InstitutionalContextResolver;
use App\Modules\Ranking\Services\ProcessarFatoDoRanking;
use App\Modules\Ranking\Services\RegraVigenteRepository;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton(ConfirmScoreTransaction::class);
        $this->app->singleton(RegraVigenteRepository::class);
        $this->app->singleton(ProcessarFatoDoRanking::class);

        // Um adaptador por dominio, resolvidos por tag: adicionar modulo novo
        // ao ranking passa a ser registrar o adaptador aqui, sem tocar no
        // listener nem no orquestrador.
        $this->app->singleton(RatAdapter::class);
        $this->app->singleton(PaeAdapter::class);
        $this->app->tag([RatAdapter::class, PaeAdapter::class], 'ranking.adaptadores');

        $this->app->bind(PontuarFatoDeNegocio::class, fn ($app) => new PontuarFatoDeNegocio(
            $app->tagged('ranking.adaptadores'),
            $app->make(ProcessarFatoDoRanking::class),
        ));

        // Leitura do placar usa a conexao de SELECT, separada da de escrita.
        $this->app->singleton(LeaderboardQuery::class, fn ($app) => new LeaderboardQuery(
            DB::connection((string) config('ranking.conexao_leitura', 'ranking_read')),
            $app->make(CacheRepository::class),
            (int) config('ranking.placar.cache_segundos', 60),
        ));
    }

    public function boot(): void
    {
        // Modulo desligado nao escuta: sem isto o ranking consumiria evento e
        // gravaria livro antes de existir placar. O proprio orquestrador tambem
        // checa a flag, mas nem registrar o listener e mais barato e evita
        // ocupar worker de fila a toa.
        if (! config('ranking.habilitado', false)) {
            return;
        }

        $eventos = [
            RegistroCompletoV1::class,
            RelatorioFinalizadoV1::class,
            VistoriaValidadaV1::class,
            ProtocoloEnviadoV1::class,
            FormularioValidadoV1::class,
            RevisaoAceitaV1::class,
            ParecerConcluidoV1::class,
        ];

        foreach ($eventos as $evento) {
            Event::listen($evento, [PontuarFatoDeNegocio::class, 'handle']);
        }
    }
}
