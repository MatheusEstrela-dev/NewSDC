<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Support\LimiteDoCronograma;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\CronoViagem
 *
 * Serializa a viagem com o contexto que a decisao de aprovar exige.
 *
 * A versao anterior entregava quatro colunas -- numero do cronograma, placa,
 * data e observacao -- e quem aprovava nao via municipio, prestador, volume,
 * valor nem se o cronograma ainda estava vigente. Decidia no escuro.
 *
 * Nada aqui exige coluna nova: tudo sai da cadeia
 * CronoViagem -> CronoCaminhao -> Cronograma/Caminhao, que o service ja
 * carrega. `marca` e `modelo` do caminhao, por exemplo, sempre vieram no eager
 * load e eram descartados na serializacao.
 */
class CronoViagemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cc = $this->cronoCaminhao;
        $cronograma = $cc?->cronograma;
        $caminhao = $cc?->caminhao;

        // O m3 desta viagem e a capacidade do caminhao: e a mesma conta que
        // CronoCaminhaoService::recalcularEntregas usa para fechar o entregue
        // (validadas x capacidade_m3). Repetir a regra aqui manteria duas
        // versoes da mesma verdade, entao o numero vem da mesma origem.
        $capacidade = $caminhao !== null ? (float) $caminhao->capacidade_m3 : null;
        $valorM3 = $cronograma?->lote !== null ? (float) $cronograma->lote->valor_m3 : null;

        return [
            'id'                => $this->id,
            'crono_caminhao_id' => $this->crono_caminhao_id,
            'status'            => $this->status,
            'validado'          => $this->validado,
            'data_registro'     => $this->data_registro?->toIso8601String(),
            'data_aprovacao'    => $this->data_aprovacao?->toIso8601String(),
            'obs'               => $this->obs,
            'obs_aprovacao'     => $this->obs_aprovacao,
            'created_at'        => $this->created_at?->toIso8601String(),

            'validador' => $this->whenLoaded('validador', fn (): array => [
                'id'   => $this->validador?->id,
                'name' => $this->validador?->name,
            ]),

            /* Contexto do cronograma */
            'cronograma_id'     => $cronograma?->id,
            'cronograma_numero' => $cronograma?->numero,
            'cronograma_estado' => $cronograma?->estado,
            'municipio_nome'    => $cronograma?->municipio?->nome,
            'prestador_nome'    => $cronograma?->prestador?->nome,
            'lote_numero'       => $cronograma?->lote?->numero,

            /* Veiculo */
            'caminhao_placa'    => $caminhao?->placa,
            'caminhao_marca'    => $caminhao?->marca,
            'caminhao_modelo'   => $caminhao?->modelo,
            'caminhao_ativo'    => $caminhao !== null ? (bool) $caminhao->ativo : null,
            'capacidade_m3'     => $capacidade,

            /* O que aprovar esta viagem libera */
            'm3_da_viagem'    => $capacidade,
            'valor_da_viagem' => $capacidade !== null && $valorM3 !== null
                ? round($capacidade * $valorM3, 2)
                : null,

            /* Prazo -- mesmo contrato que AtaIndexResource ja expoe */
            'dt_final_efetiva' => $cronograma?->dt_final_efetiva?->toDateString(),
            'dias_restantes'   => $cronograma?->dias_restantes,
            'proxima_vencer'   => (bool) ($cronograma?->proxima_vencer ?? false),

            /* Sinais de risco, para a tela destacar sem recalcular nada */
            'dias_aguardando'  => $this->diasAguardando(),
            'fora_da_vigencia' => $this->foraDaVigencia(),

            /* Decisao do municipio (COMPDEC) */
            'status_confirmacao' => $this->status_confirmacao,
            'confirmado_em'      => $this->confirmado_em?->toIso8601String(),
            'obs_confirmacao'    => $this->obs_confirmacao,
            // Mesma regra que o service aplica ao decidir: a tela desabilita
            // exatamente o que o servidor recusaria.
            'pode_decidir_confirmacao' => $this->status_confirmacao === 'pendente'
                && LimiteDoCronograma::permiteDecisao($cronograma, $this->data_registro),
        ];
    }

    /** Ha quantos dias a viagem espera decisao. Fila velha e o primeiro alerta. */
    private function diasAguardando(): ?int
    {
        if ($this->data_registro === null) {
            return null;
        }

        return (int) $this->data_registro->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);
    }

    /**
     * Viagem registrada fora da vigencia do cronograma.
     *
     * SINALIZACAO, NUNCA BLOQUEIO. CronoViagemService documenta que 1.165 das
     * 3.808 viagens da base (31%) tem data anterior ao dt_inicio do proprio
     * cronograma -- ou o acervo legado esta errado, ou `data_registro` nao
     * significa "dia da viagem" nesta operacao. Transformar isso em regra
     * quebraria a operacao; mostrar na tela deixa a pessoa decidir.
     */
    private function foraDaVigencia(): bool
    {
        $cronograma = $this->cronoCaminhao?->cronograma;

        if ($cronograma === null || $this->data_registro === null) {
            return false;
        }

        $dia = $this->data_registro->copy()->startOfDay();
        $inicio = $cronograma->dt_inicio_efetiva?->copy()->startOfDay();
        $fim = $cronograma->dt_final_efetiva?->copy()->startOfDay();

        return ($inicio !== null && $dia->lessThan($inicio))
            || ($fim !== null && $dia->greaterThan($fim));
    }
}
