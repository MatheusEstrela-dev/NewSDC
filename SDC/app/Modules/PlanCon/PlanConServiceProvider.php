<?php

declare(strict_types=1);

namespace App\Modules\PlanCon;

use Illuminate\Support\ServiceProvider;

/**
 * Painel estadual de cobertura de Plano de Contingencia.
 *
 * O modulo nao tem model nem repositorio proprio: le
 * compdec_planos_contingencia, cujo dono e o modulo COMPDEC. O provider fica
 * registrado porque config/app.php o lista e o modulo pode voltar a precisar
 * de bindings.
 */
class PlanConServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
