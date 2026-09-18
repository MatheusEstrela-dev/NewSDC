<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Support\Documento;
use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Caminhao
 *
 * O veiculo com a sua situacao de vistoria.
 *
 * `ativo` responde "esta no cadastro?"; `apto` responde "pode rodar?". Eram
 * lidas como a mesma coisa em duas telas separadas, e nao sao: a frota tem 132
 * ativos e 2 aptos.
 */
class CaminhaoIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $vigente = $this->whenLoaded('vistoriaVigente', fn () => $this->vistoriaVigente);
        $ultima = $this->whenLoaded('ultimaVistoria', fn () => $this->ultimaVistoria);

        return [
            'id'             => $this->id,
            'placa'          => $this->placa,
            'marca'          => $this->marca,
            'modelo'         => $this->modelo,
            'ano'            => $this->ano,
            'capacidade_m3'  => (float) $this->capacidade_m3,
            'ativo'          => (bool) $this->ativo,
            'prestador_nome' => $this->whenLoaded('prestador', fn () => $this->prestador->nome),
            // Mascarado na saida: a coluna guarda somente digitos (ver
            // Tdap\Support\Documento) e "37628085000167" na tela nao e legivel.
            'prestador_cnpj' => $this->whenLoaded('prestador', fn () => Documento::cnpj($this->prestador->cnpj)),

            /* Situacao de vistoria -- o que a outra tela guardava */
            //
            // `relationLoaded` explicito: sem a relacao carregada, `whenLoaded`
            // devolve MissingValue e `apto` saia `false` -- indistinguivel de
            // "nao tem vistoria vigente". Quem esquecesse o eager load veria a
            // frota inteira reprovada e nada apontaria o motivo. Agora esse
            // caso vem `null`: nao sabemos, e a tela nao finge que sabe.
            'apto'              => $this->relationLoaded('vistoriaVigente')
                ? $vigente instanceof Vistoria
                : null,
            'situacao_vistoria' => $this->situacaoDaVistoria(),
            'total_vistorias'   => $this->whenCounted('vistorias'),

            'vistoria' => $ultima instanceof Vistoria ? [
                'id'             => $ultima->id,
                'data'           => $ultima->data?->toDateString(),
                // Sem fallback `?? (string) $parecer`: com o cast de enum ativo
                // esse caminho nunca roda, e se rodasse lancaria (enum nao vira
                // string). Era protecao da epoca em que a coluna vinha crua.
                'parecer'        => $ultima->parecer?->value,
                'vistoriador'    => $ultima->nome,
                'ficha'          => $ultima->ficha,
                'lacre'          => $ultima->lacre,
                // Dias ate o fim dos 12 meses de vigencia, no mesmo contrato
                // assinado que o resto do modulo usa: negativo = vencida.
                'dias_restantes' => $this->diasDeVigenciaRestantes($ultima),
            ] : null,
        ];
    }

    /**
     * `apto` | `vencida` | `sem_vistoria`.
     *
     * Tres estados, e nao dois: um caminhao sem vistoria nenhuma e um cuja
     * vistoria venceu exigem acoes diferentes -- cadastrar e renovar. Tratar os
     * dois como "nao apto" esconde essa diferenca de quem vai agir.
     */
    private function situacaoDaVistoria(): string
    {
        if ($this->relationLoaded('vistoriaVigente') && $this->vistoriaVigente !== null) {
            return 'apto';
        }

        $temHistorico = $this->relationLoaded('ultimaVistoria')
            ? $this->ultimaVistoria !== null
            : ($this->vistorias_count ?? 0) > 0;

        return $temHistorico ? 'vencida' : 'sem_vistoria';
    }

    /**
     * Vigencia da vistoria: 12 meses a contar da data dela.
     *
     * Antes reusava VigenciaAta, que e de outro agregado: o prazo da vistoria
     * nao tem relacao com o prazo da ata, e a coincidencia de ambos serem
     * "dias ate uma data" nao faz deles a mesma regra.
     */
    private function diasDeVigenciaRestantes(Vistoria $vistoria): ?int
    {
        return VigenciaVistoria::diasRestantes($vistoria->data);
    }
}
