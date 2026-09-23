<?php

declare(strict_types=1);

namespace App\Modules\Inventario;

use App\Modules\Inventario\Contracts\EquipamentoRepository;
use App\Modules\Inventario\Infrastructure\EloquentEquipamentoRepository;
use Illuminate\Support\ServiceProvider;

class InventarioServiceProvider extends ServiceProvider
{
    public array $bindings = [
        EquipamentoRepository::class => EloquentEquipamentoRepository::class,
    ];
}
