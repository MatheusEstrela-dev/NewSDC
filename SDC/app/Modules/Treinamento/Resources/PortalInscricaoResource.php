<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Http\Request;

/**
 * Inscricoes do portal: a do SDC mais a capa do curso servida pela rota do
 * cidadao, e o certificado na visao do proprio aluno (so liberado depois da
 * avaliacao do curso).
 */
class PortalInscricaoResource extends InscricaoResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'treinamento_capa_url' => $this->whenLoaded('treinamento', fn () => $this->treinamento->capaUrl(Treinamento::ROTA_CAPA_PORTAL)),
            'certificado' => $this->whenLoaded('certificado', function () {
                if ($this->certificado === null) {
                    return null;
                }
                // A regra le a avaliacao pela inscricao: reaproveita esta, ja carregada.
                $this->certificado->setRelation('inscricao', $this->resource);

                return new CertificadoDoAlunoResource($this->certificado);
            }),
        ]);
    }
}
