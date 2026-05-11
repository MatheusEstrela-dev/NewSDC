<?php

declare(strict_types=1);

namespace App\Modules\Tdap;

use Illuminate\Support\ServiceProvider;

/**
 * TDAP - Transporte e Distribuicao de Agua Potavel.
 *
 * Gerencia o ciclo de contratacao e execucao do fornecimento emergencial de
 * agua potavel por prestadores de servico em municipios afetados (Atas, Lotes,
 * Cronogramas, Caminhoes, Viagens, Vistorias e Historico).
 *
 * Plano de migracao: docs/superpowers/plans/2026-05-11-tdap-migration.md
 *
 * Arquitetura DDD (espelha app/Modules/Rat/):
 *   - Domain/Repositories/       Contratos
 *   - Infrastructure/Persistence Implementacoes Eloquent
 *   - Application/UseCases/      Fluxos multi-step
 *   - Services/                  Service Layer SOLID
 *   - DTOs/                      Imutaveis (PHP 8 readonly)
 *   - Http/Requests, Resources
 *   - Models/                    Eloquent anemic
 *   - Enums/                     PHP 8.1 backed
 *
 * Rotas: routes/modules/tdap.php (carregado via routes/web.php no middleware auth).
 */
class TdapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings Interface => Implementation sao registrados a partir da Fase 1.
    }

    public function boot(): void
    {
        // Observers e Event Listeners sao registrados nas Fases 5 e 6.
    }
}
