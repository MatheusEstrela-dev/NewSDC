<?php

declare(strict_types=1);

namespace App\Http\Resources\Pae;

use App\Modules\Pae\Support\PrazoNotificacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaeNotificacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dias = $this->resource->diasDilacao();
        $prazoFinal = PrazoNotificacao::vencimento($this->dt_notificacao, $dias);

        return [
            'id'             => $this->id,
            'num_sei'        => $this->num_sei,
            'dt_notificacao' => $this->dt_notificacao->toDateString(),
            'prazo_final'    => $prazoFinal->toDateString(),
            'dt_devolutiva'  => $this->dt_devolutiva?->toDateString(),
            'vencida'        => PrazoNotificacao::vencida($this->dt_notificacao, $dias, $this->dt_devolutiva),
            'obs'            => $this->obs,
        ];
    }
}
