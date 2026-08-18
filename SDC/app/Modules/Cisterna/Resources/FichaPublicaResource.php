<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Beneficiario na ficha PUBLICA do QR Code.
 *
 * Existe porque a rota da ficha sai do grupo de autenticacao: ela e lida por
 * quem escaneia o adesivo colado na cisterna, sem login. Usar o
 * BeneficiarioResource ali vazava dado pessoal -- CPF, nome, renda, deficiencia
 * e chefia feminina -- e nao bastaria deixar de desenhar na tela: o Inertia
 * embute TODAS as props no atributo data-page do HTML, legivel por qualquer um
 * que abra o codigo-fonte da pagina.
 *
 * O recorte e o mesmo da ficha do legado (qrcode.blade.php): so localizacao da
 * instalacao, o suficiente para conferir que o adesivo corresponde ao imovel.
 *
 * @property-read \App\Modules\Cisterna\Models\CisternaBeneficiario $resource
 */
class FichaPublicaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'municipio' => $this->whenLoaded(
                'municipio',
                fn (): ?array => $this->municipio === null ? null : [
                    'nome' => $this->municipio->nome,
                    'uf' => $this->municipio->uf,
                ]
            ),
            'comunidade' => $this->whenLoaded(
                'comunidade',
                fn (): ?array => $this->comunidade === null ? null : [
                    'nome' => $this->comunidade->nome,
                ]
            ),
            'endereco' => $this->endereco,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
