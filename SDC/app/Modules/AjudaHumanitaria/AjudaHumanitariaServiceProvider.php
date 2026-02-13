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
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuários (sessão e autenticação ausentes).
     * Padrão: mesmo que RatServiceProvider.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/ajuda-humanitaria.php
    }
}
