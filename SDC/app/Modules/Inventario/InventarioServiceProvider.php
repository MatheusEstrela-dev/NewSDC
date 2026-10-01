<?php

declare(strict_types=1);

namespace App\Modules\Inventario;

use App\Modules\Inventario\Contracts\EquipamentoRepository;
use App\Modules\Inventario\Infrastructure\EloquentEquipamentoRepository;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Observers\RemanejamentoTempoRealObserver;
use Illuminate\Support\ServiceProvider;

class InventarioServiceProvider extends ServiceProvider
{
    public array $bindings = [
        EquipamentoRepository::class => EloquentEquipamentoRepository::class,
    ];

    public function boot(): void
    {
        Remanejamento::observe(RemanejamentoTempoRealObserver::class);
    }
}
