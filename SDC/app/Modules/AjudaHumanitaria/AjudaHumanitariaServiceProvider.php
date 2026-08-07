<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Console\ExtrairLegadoAjuCommand;
use App\Modules\AjudaHumanitaria\Console\RefinarLegadoAjuCommand;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItemNoPedido;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItensLiberados;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeParecerFavoravel;
use App\Modules\AjudaHumanitaria\Domain\Guards\FinalizacaoSomenteViaHomologacao;
use App\Modules\AjudaHumanitaria\Domain\PedidoAhWorkflow;
use App\Modules\AjudaHumanitaria\Domain\Repositories\MaterialAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentMaterialAhRepository;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPedidoAhRepository;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentPrestacaoContaRepository;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\PostgresSaldoMaterialRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider do modulo Ajuda Humanitaria.
 *
 * Concentra as duas listas que governam o modulo: as guardas de transicao e os
 * contratos de persistencia. Ambas sao pontos de extensao declarativos, e nao
 * exigem alteracao em service algum.
 */
class AjudaHumanitariaServiceProvider extends ServiceProvider
{
    /**
     * Guardas consultadas pelo workflow a cada transicao de status.
     *
     * Para acrescentar uma regra de transicao, implemente GuardaTransicao e
     * inclua a classe aqui. A ordem e irrelevante: uma guarda que nao se aplica
     * ao par de status do contexto permite em vez de bloquear.
     *
     * @var array<int, class-string<\App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao>>
     */
    private const GUARDAS_TRANSICAO = [
        ExigeItemNoPedido::class,
        ExigeParecerFavoravel::class,
        ExigeItensLiberados::class,
        FinalizacaoSomenteViaHomologacao::class,
    ];

    /**
     * Contratos de dominio e suas implementacoes concretas.
     *
     * A troca de qualquer implementacao acontece aqui, em uma linha. O saldo de
     * material ja fez esse caminho: vinha por leitura da base legada do
     * gestaocedec e passou a sair do estoque nativo em Postgres, sem que nada
     * no dominio mudasse. LegadoSaldoMaterialRepository segue no repositorio
     * como rota de volta ate o corte definitivo do legado.
     *
     * @var array<class-string, class-string>
     */
    private const REPOSITORIOS = [
        PedidoAhRepositoryInterface::class       => EloquentPedidoAhRepository::class,
        PrestacaoContaRepositoryInterface::class => EloquentPrestacaoContaRepository::class,
        MaterialAhRepositoryInterface::class     => EloquentMaterialAhRepository::class,
        SaldoMaterialRepositoryInterface::class  => PostgresSaldoMaterialRepository::class,
    ];

    /**
     * Comandos de console do modulo.
     *
     * A carga do legado vive aqui em vez de em routes/console.php porque e
     * trabalho de migracao do proprio modulo, e sai junto com ele no corte.
     *
     * @var array<int, class-string<\Illuminate\Console\Command>>
     */
    private const COMANDOS = [
        ExtrairLegadoAjuCommand::class,
        RefinarLegadoAjuCommand::class,
    ];

    public function register(): void
    {
        foreach (self::REPOSITORIOS as $contrato => $implementacao) {
            $this->app->bind($contrato, $implementacao);
        }

        $this->app->singleton(PedidoAhWorkflow::class, function ($app): PedidoAhWorkflow {
            $guardas = array_map(
                static fn (string $guarda) => $app->make($guarda),
                self::GUARDAS_TRANSICAO,
            );

            return new PedidoAhWorkflow($guardas);
        });
    }

    /**
     * As rotas do modulo sao carregadas por routes/web.php dentro do grupo de
     * middleware auth, que inclui web. Nao usar loadRoutesFrom aqui: isso
     * registraria rotas sem sessao e sem autenticacao, resultando em 403 para
     * todos os usuarios. Mesmo padrao do RatServiceProvider.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(self::COMANDOS);
        }
    }
}
