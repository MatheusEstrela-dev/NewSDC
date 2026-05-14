<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Observers;

use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Services\HistoricoService;

class VistoriaObserver
{
    public function __construct(
        private readonly HistoricoService $historicoService,
    ) {}

    public function created(Vistoria $vistoria): void
    {
        $tipo = $vistoria->parecer === ParecerVistoria::Aprovada
            ? 'vistoria.aprovada'
            : 'vistoria.reprovada';

        $obs = $vistoria->parecer === ParecerVistoria::Aprovada
            ? "Vistoria #{$vistoria->id} aprovada (vigencia 12 meses)."
            : "Vistoria #{$vistoria->id} reprovada.";

        $this->historicoService->registrar(
            tipoEvento: $tipo,
            entity: $vistoria,
            obs: $obs,
            payload: [
                'placa_id' => $vistoria->placa_id,
                'data'     => $vistoria->data?->toDateString(),
                'parecer'  => $vistoria->parecer->value,
                'ficha'    => $vistoria->ficha,
            ],
        );
    }

    public function updated(Vistoria $vistoria): void
    {
        if (! $vistoria->wasChanged('parecer')) {
            return;
        }

        $tipo = $vistoria->parecer === ParecerVistoria::Aprovada
            ? 'vistoria.aprovada'
            : 'vistoria.reprovada';

        $this->historicoService->registrar(
            tipoEvento: $tipo,
            entity: $vistoria,
            obs: "Parecer da vistoria #{$vistoria->id} alterado para {$vistoria->parecer->label()}.",
            payload: [
                'parecer_anterior' => $vistoria->getOriginal('parecer'),
                'parecer_novo'     => $vistoria->parecer->value,
            ],
        );
    }
}
