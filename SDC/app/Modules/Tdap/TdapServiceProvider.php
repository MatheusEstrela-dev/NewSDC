<?php

declare(strict_types=1);

namespace App\Modules\Tdap;

use App\Modules\Tdap\Services\CaminhaoService;
use App\Modules\Tdap\Services\PrestadorService;
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
 * Padrao DDD-minimo (espelha app/Modules/Cisterna/, Compdec/):
 *   - Controllers/  Thin, injetam Service via constructor
 *   - DTOs/         Imutaveis (readonly), com fromRequest()/toArray()
 *   - Enums/        PHP 8.1 backed
 *   - Models/       Eloquent anemic + relacionamentos + scopes
 *   - Observers/    Hooks de evento Eloquent (Fase 5)
 *   - Requests/     Form Requests com authorize() + rules()
 *   - Resources/    JSON Resources (Index + Show separados)
 *   - Services/     Service Layer (uma responsabilidade por classe)
 */
class TdapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Cadastros base (Fase 1)
        $this->app->singleton(PrestadorService::class);
        $this->app->singleton(CaminhaoService::class);
    }

    public function boot(): void
    {
        // Rotas carregadas via routes/web.php (require routes/modules/tdap.php)
        // Permissoes sincronizadas via config/permissions.php + RolesAndPermissionsSeeder
        // Observers e Event Listeners sao registrados nas Fases 5 e 6.
    }
}
