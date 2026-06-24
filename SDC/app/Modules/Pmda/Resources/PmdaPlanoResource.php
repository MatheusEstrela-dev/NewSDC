<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmdaPlanoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'protocolo'         => $this->protocolo,
            'municipio_id'      => $this->municipio_id,
            'municipio'         => $this->whenLoaded('municipio', fn () => $this->municipio->nome ?? null),
            'status'            => $this->status->value,
            'status_label'      => $this->status->getLabel(),
            'status_color'      => $this->status->getColorClass(),
            'pode_copiar'       => $this->status->permiteCopia(),
            'data'              => $this->data?->toIso8601String(),
            'acoes'             => $this->acoes,
            'qtd_caminhao'      => $this->qtd_caminhao,
            'pop_at_municipio'  => $this->pop_at_municipio,
            'cobra_iss'         => $this->cobra_iss,
            'num_lei_iss'       => $this->num_lei_iss,
            'aliquota_iss'      => $this->aliquota_iss,
            'resp_cob_iss'      => $this->resp_cob_iss,
            // Municipio / Prefeitura
            'nome_prefeito'     => $this->nome_prefeito,
            'tel_prefeitura'    => $this->tel_prefeitura,
            'tel_prefeito'      => $this->tel_prefeito,
            'cel_prefeito'      => $this->cel_prefeito,
            'endereco'          => $this->endereco,
            'bairro'            => $this->bairro,
            'cep'               => $this->cep,
            'email_prefeitura'  => $this->email_prefeitura,
            'populacao'         => $this->populacao,
            'pop_rural'         => $this->pop_rural,
            'area'              => $this->area,
            // COMPDEC
            'compdec_coordenador' => $this->compdec_coordenador,
            'compdec_decreto'     => $this->compdec_decreto,
            'compdec_lei'         => $this->compdec_lei,
            'compdec_tel'         => $this->compdec_tel,
            'compdec_email'       => $this->compdec_email,
            'pontos'            => $this->whenLoaded('pontos', fn () => $this->pontos->map(fn ($p) => [
                'id'         => $p->id,
                'nome'       => $p->nome,
                'capacidade' => $p->capacidade,
            ])->values()),
            'data_aprov'        => $this->data_aprov?->toIso8601String(),
            'dt_ultima_alteracao' => $this->dt_ultima_alteracao?->toIso8601String(),
            'comunidades_count' => $this->whenCounted('comunidades'),
            'comunidades'       => ComunidadeResource::collection($this->whenLoaded('comunidades')),
        ];
    }
}
