<?php

declare(strict_types=1);

namespace App\Modules\Ranking;

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
        // Services sao registrados nas fases seguintes, conforme forem criados.
        // Todos devem ser stateless pelo motivo descrito no docblock acima.
    }

    public function boot(): void
    {
        // Os listeners dos eventos de RAT e PAE sao ligados na Fase 4, e apenas
        // quando config('ranking.habilitado') estiver ativo. Registrar listener
        // com o modulo desligado faria o Ranking consumir evento e gravar livro
        // sem que o placar exista.
    }
}
