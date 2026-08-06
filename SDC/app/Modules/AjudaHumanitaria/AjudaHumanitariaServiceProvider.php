<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeAgendamentoAprovado;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItemNoPedido;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItensLiberados;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeParecerFavoravel;
use App\Modules\AjudaHumanitaria\Domain\Guards\FinalizacaoSomenteViaHomologacao;
use App\Modules\AjudaHumanitaria\Domain\PedidoAhWorkflow;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider do modulo Ajuda Humanitaria.
 *
 * As guardas de transicao sao declaradas aqui, em um unico lugar. Para
 * acrescentar uma regra de transicao, implemente GuardaTransicao e inclua a
 * classe em GUARDAS_TRANSICAO: nenhum service precisa mudar.
 *
 * Os binds de repositorio entram na fase 2, junto com as implementacoes sob
 * Infrastructure/Persistence.
 */
class AjudaHumanitariaServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, class-string<\App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao>>
     */
    private const GUARDAS_TRANSICAO = [
        ExigeItemNoPedido::class,
        ExigeParecerFavoravel::class,
        ExigeItensLiberados::class,
        ExigeAgendamentoAprovado::class,
        FinalizacaoSomenteViaHomologacao::class,
    ];

    public function register(): void
    {
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
        //
    }
}
