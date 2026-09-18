<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Vistoria
 *
 * Uma inspecao na serie historica do caminhao (modal da tela de frota).
 *
 * Existe separada de VistoriaIndexResource porque a pergunta e outra: a
 * listagem responde "quais vistorias existem"; a serie responde "este veiculo e
 * confiavel, e ate quando ele esta coberto". Dai `valido_ate` e
 * `dias_restantes`, que a listagem nao carrega, e a ausencia dos dados do
 * caminhao, que no modal ja estao no cabecalho.
 *
 * O payload era montado a mao dentro do FrotaVistoriaController -- terceira
 * representacao da mesma entidade no mesmo submodulo, e a unica sem Resource.
 * Os NOMES DOS CAMPOS foram preservados exatamente (`vistoriador`, `vigente`),
 * embora divirjam dos outros dois Resources (`nome`, `esta_vigente`):
 * renomear aqui quebraria o VistoriaHistoricoModal.vue, e alinhar os tres nomes
 * e decisao de contrato, nao de arrumacao.
 */
class VistoriaSerieResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'data'           => $this->data?->toDateString(),
            'parecer'        => $this->parecer?->value,
            'parecer_label'  => $this->parecer?->label(),
            'vistoriador'    => $this->nome,
            'ficha'          => $this->ficha,
            'lacre'          => $this->lacre,
            'edital'         => $this->edital,
            'observacoes'    => $this->observacoes,
            'vigente'        => (bool) $this->esta_vigente,
            'valido_ate'     => $this->valido_ate?->toDateString(),
            // Vigencia assinada: negativo = venceu. Sai de VigenciaVistoria, a
            // mesma fonte do accessor.
            'dias_restantes' => VigenciaVistoria::diasRestantes($this->data),
        ];
    }
}
