<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Uma linha da listagem estadual de prefeituras.
 *
 * O `resource` que chega aqui e o stdClass que o Query Builder devolve em
 * CedecPrefeituraService::listar() -- municipios LEFT JOIN compdec_prefeituras, ja
 * com tem_foto calculado -- e nao um Model Eloquent. stdClass aceita a mesma sintaxe
 * `$this->campo`, entao o Resource nao muda por isso.
 *
 * prefeitura_id existe na linha crua, para o calculo de tem_foto, mas NAO sai daqui:
 * o frontend navega por municipio.
 */
final class PrefeituraListaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'municipio_id' => (int) $this->municipio_id,
            'municipio_nome' => (string) $this->municipio_nome,
            'codigo_ibge' => (string) $this->codigo_ibge,
            'redec' => $this->redec,
            'prefeito_nome' => $this->prefeito_nome,
            'email_prefeitura' => $this->email_prefeitura,
            'tel_prefeitura' => $this->tel_prefeitura,
            'tem_foto' => (bool) $this->tem_foto,
        ];
    }
}
