<?php

declare(strict_types=1);

namespace App\Modules\Inventario;

use App\Modules\Inventario\Contracts\EquipamentoRepository;
use App\Modules\Inventario\Infrastructure\EloquentEquipamentoRepository;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Observers\RemanejamentoTempoRealObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class InventarioServiceProvider extends ServiceProvider
{
    public array $bindings = [
        EquipamentoRepository::class => EloquentEquipamentoRepository::class,
    ];

    public function boot(): void
    {
        Remanejamento::observe(RemanejamentoTempoRealObserver::class);
        $this->registrarLimiteSeplag();
    }

    /**
     * Balde proprio por usuario + lote. O throttle:6,1 cru usava so o id do
     * usuario e dividia o contador com o polling de notificacoes e as rotas de
     * auth: com algumas abas abertas o primeiro envio ja voltava 429. Estourado,
     * volta com erro de sessao `remanejamento` (inline na listagem) em vez da
     * pagina 429, mesma politica do login e do esqueci-a-senha.
     */
    private function registrarLimiteSeplag(): void
    {
        RateLimiter::for('inventario-seplag', function (Request $request) {
            $lote = $request->route('remanejamento');
            $loteId = $lote instanceof Model ? (string) $lote->getKey() : (string) $lote;

            return Limit::perMinute(6)
                ->by($request->user()?->id . '|' . $loteId)
                ->response(fn () => back()->withErrors([
                    'remanejamento' => 'Muitos envios deste lote à SEPLAG em pouco tempo. Aguarde um minuto e tente novamente.',
                ]));
        });
    }
}
