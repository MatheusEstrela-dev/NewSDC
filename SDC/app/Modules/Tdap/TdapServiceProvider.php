<?php

namespace App\Modules\Tdap;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\Tdap\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\ProductLoteRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\RecebimentoRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\MovimentacaoRepositoryInterface;
use App\Modules\Tdap\Infrastructure\Persistence\EloquentProductRepository;
use App\Modules\Tdap\Infrastructure\Persistence\EloquentProductLoteRepository;
use App\Modules\Tdap\Infrastructure\Persistence\EloquentRecebimentoRepository;
use App\Modules\Tdap\Infrastructure\Persistence\EloquentMovimentacaoRepository;

class TdapServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços do módulo
     */
    public function register(): void
    {
        // Registrar bindings dos repositórios
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ProductLoteRepositoryInterface::class, EloquentProductLoteRepository::class);
        $this->app->bind(RecebimentoRepositoryInterface::class, EloquentRecebimentoRepository::class);
        $this->app->bind(MovimentacaoRepositoryInterface::class, EloquentMovimentacaoRepository::class);

        // Registrar Use Cases (singleton para melhor performance)
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\ListProductsUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\CreateProductUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\GetEstoqueUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\GetDashboardDataUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\ListMovimentacoesUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\CreateSaidaEstoqueUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\GetProductHistoricoUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\ListRecebimentosUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\ShowRecebimentoUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\CreateRecebimentoUseCase::class);
        $this->app->singleton(\App\Modules\Tdap\Application\UseCases\ProcessarRecebimentoUseCase::class);
    }

    /**
     * Bootstrap dos serviços do módulo
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM o middleware 'auth', causando
     * 403 para todos os usuários. Padrão: mesmo que RatServiceProvider.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/tdap.php
    }
}
