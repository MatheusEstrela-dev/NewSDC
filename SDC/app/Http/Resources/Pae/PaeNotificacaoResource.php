<?php

declare(strict_types=1);

namespace App\Http\Resources\Pae;

use App\Modules\Pae\Services\PaeNotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaeNotificacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $prazoFinal = $this->dt_notificacao
            ->copy()
            ->addDays(PaeNotificacaoService::PRAZO_DIAS);

        return [
            'id'             => $this->id,
            'num_sei'        => $this->num_sei,
            'dt_notificacao' => $this->dt_notificacao->toDateString(),
            'prazo_final'    => $prazoFinal->toDateString(),
            'dt_devolutiva'  => $this->dt_devolutiva?->toDateString(),
            'vencida'        => ! $this->dt_devolutiva && $prazoFinal->isBefore(now()->startOfDay()),
            'obs'            => $this->obs,
        ];
    }
}
