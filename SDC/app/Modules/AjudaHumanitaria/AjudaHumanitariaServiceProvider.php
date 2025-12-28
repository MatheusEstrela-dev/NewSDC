<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria;

use App\Modules\AjudaHumanitaria\Domain\Repositories\BeneficiarioRepositoryInterface;
use App\Modules\AjudaHumanitaria\Infrastructure\Persistence\EloquentBeneficiarioRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Módulo Ajuda Humanitária
 *
 * Registra todas as dependências do módulo de gestão de ajuda humanitária
 */
class AjudaHumanitariaServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind Repository Interfaces to Eloquent Implementations
        $this->app->bind(
            BeneficiarioRepositoryInterface::class,
            EloquentBeneficiarioRepository::class
        );

        // TODO: Registrar demais repositories quando forem criados
        // $this->app->bind(AbrigoRepositoryInterface::class, EloquentAbrigoRepository::class);
        // $this->app->bind(DoacaoRepositoryInterface::class, EloquentDoacaoRepository::class);
        // $this->app->bind(AuxilioRepositoryInterface::class, EloquentAuxilioRepository::class);
        // $this->app->bind(EstoqueRepositoryInterface::class, EloquentEstoqueRepository::class);

        // TODO: Registrar Use Cases quando forem criados
        // $this->app->singleton(CreateBeneficiarioUseCase::class);
        // $this->app->singleton(ListBeneficiariosUseCase::class);
        // etc...
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Carregar rotas do módulo
        $routesPath = base_path('routes/modules/ajuda-humanitaria.php');
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Carregar migrations
        $this->loadMigrationsFrom(database_path('migrations'));

        // TODO: Registrar observers para eventos
        // Beneficiario::observe(BeneficiarioObserver::class);
        // Abrigo::observe(AbrigoObserver::class);

        // TODO: Registrar policies
        // Gate::policy(Beneficiario::class, BeneficiarioPolicy::class);

        // TODO: Registrar event listeners
        // Event::listen(BeneficiarioCriado::class, NotificarEquipeListener::class);
        // Event::listen(AuxilioDistribuido::class, AtualizarEstoqueListener::class);
    }
}
