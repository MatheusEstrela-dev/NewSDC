<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dadosGerais = $this->relatosMorph
            ->first(fn($r) => $r->conteudo instanceof \App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais)
            ?->conteudo;

        $recursos = $this->relatosMorph
            ->filter(fn($r) => $r->conteudo instanceof \App\Modules\Rat\Models\Relatos\RatRelatoRecurso)
            ->map(fn($r) => $r->conteudo?->toArray())
            ->values();

        $envolvidos = $this->relatosMorph
            ->filter(fn($r) => $r->conteudo instanceof \App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos)
            ->map(fn($r) => $r->conteudo?->toArray())
            ->values();

        $vistoria = $this->relatosMorph
            ->first(fn($r) => $r->conteudo instanceof \App\Modules\Rat\Models\Relatos\RatRelatoVistoria)
            ?->conteudo;

        return [
            'dados_gerais' => $dadosGerais?->toArray(),
            'recursos'     => $recursos,
            'envolvidos'   => $envolvidos,
            'vistoria'     => $vistoria ? ['id' => $vistoria->id] : null,
        ];
    }
}
