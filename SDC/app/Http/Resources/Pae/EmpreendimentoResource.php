<?php

declare(strict_types=1);

namespace App\Http\Resources\Pae;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpreendimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'status' => $this->status,
            'mina' => $this->mina,
            'metodo_construtivo' => $this->m_construcao,
            'material' => $this->material,
            'finalidade' => $this->finalidade,
            'volume' => $this->volume !== null ? (float) $this->volume : null,
            'populacao_zas' => $this->pop_zas,
            'orgao_fiscalizador' => $this->orgao_fisc,
            'municipio' => $this->whenLoaded('municipio', fn () => $this->municipio ? [
                'id' => $this->municipio->id,
                'nome' => $this->municipio->nome,
                'uf' => $this->municipio->uf,
            ] : null),
            'empreendedor' => $this->whenLoaded('empdor', fn () => $this->empdor ? [
                'id' => $this->empdor->id,
                'nome' => $this->empdor->nome,
                'cnpj' => $this->empdor->cnpj,
            ] : null),
            'coordenador' => [
                'nome' => $this->coordenador,
                'telefone' => $this->tel_coordenador,
                'email' => $this->email_coord,
            ],
            'coordenador_substituto' => [
                'nome' => $this->coordenador_sub,
                'telefone' => $this->tel_coordenador_sub,
                'email' => $this->email_coord_sub,
            ],
            'ultimo_protocolo' => $this->whenLoaded('latestProtocolo', function () {
                if (! $this->latestProtocolo) {
                    return null;
                }

                $status = $this->latestProtocolo->status;
                if (is_object($status) && property_exists($status, 'value')) {
                    $status = $status->value;
                }

                return [
                    'id' => $this->latestProtocolo->id,
                    'num_protocolo' => $this->latestProtocolo->num_protocolo,
                    'sigibar' => $this->latestProtocolo->sigibar,
                    'status' => $status,
                    'dt_entrada' => optional($this->latestProtocolo->dt_entrada)->toDateString(),
                    'ccpae_vencimento' => optional($this->latestProtocolo->ccpae_venc)->toDateString(),
                ];
            }),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
