<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class HexagonDecretoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $municipios = $this->municipios;
        $municipio = $municipios->first();

        $listaMunicipio = $municipios->map(function ($municipio) {
            return [
                'municipio' => $municipio->nome,
                'geocodigoIbge' => $municipio->Codmundv,
            ];
        })->toArray();

        $dataExpiracao = null;
        $diasVigenciaRestante = null;

        if ($this->data_publicacao_mg && $this->prazo_vigencia) {
            $dataExpiracaoCarbon = Carbon::parse($this->data_publicacao_mg)
                                  ->addDays($this->prazo_vigencia);

            $dataExpiracao = $dataExpiracaoCarbon->format('Y-m-d');

            $diasVigenciaRestante = Carbon::now()->diffInDays($dataExpiracaoCarbon, false);
        }

        return [
            'Id'                            => $this->logs->first()->uuid ?? "$this->id-0",
            'IdSdc'                         => $this->id,
            'Classificacao'                 => $this->tipo_desastre_nome,
            'CodClassificacao'              => $this->tipo_desastre_cobrade,
            'DataEntrada'                   => $this->data_entrada,
            'GeocodigoIbge'                 => $municipio->Codmundv,
            'Municipio'                     => $municipio->nome,
            'Mesorregiao'                   => $municipio->macroregiao,
            'Redec'                         => $municipio->rpm,
            'StatusReconhecimento'          => $this->reconhecimento,
            'Analista'                      => $this->analista,
            'Fide'                          => $this->n_protocolo_fide,
            'DataDecretoMunicipal'          => $this->data_decreto_municipal,
            'DataPublicacaoMg'              => $this->data_publicacao_mg,
            'DiasVigencia'                  => $this->prazo_vigencia,
            'DataExpiracao'                 => $dataExpiracao,
            'DiasVigenciaRestante'          => $diasVigenciaRestante,
            'TipoDecretoMunicipal'          => $this->situacao_anormalidade,
            'ReconhecimentoDecretoEstadual' => $this->reconhecimento_decreto_n_data,
            'PortariaReconhecimentoFederal' => $this->portaria_reconhecimento_fed,
            'ListaMunicipio'                => $listaMunicipio,
        ];
    }
}
