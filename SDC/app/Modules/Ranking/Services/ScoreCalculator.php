<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\ScoreDecisionData;
use App\Modules\Ranking\DTOs\ScoreRuleData;
use App\Modules\Ranking\DTOs\ScoreTransactionData;
use App\Modules\Ranking\Enums\DecisaoPontuacao;

/**
 * Calculo de pontos. FUNCAO PURA.
 *
 * Sem banco, sem Auth, sem sessao, sem request: recebe o fato ja comprovado
 * pelo adaptador e a versao da regra escolhida pelo chamador, e devolve a
 * decisao. Duas razoes:
 *
 * 1. Testabilidade: a tabela de pontuacao precisa ser verificavel em teste
 *    unitario, sem subir Laravel nem Postgres.
 * 2. Octane: este service e singleton e sobrevive entre requests do worker.
 *    Estado guardado aqui vazaria de um usuario para o proximo. O percentual do
 *    bonus e o teto chegam pelo construtor exatamente para nao serem lidos de
 *    config() em tempo de calculo.
 *
 * Estorno NAO e tratado aqui: ScoreDecisionData recusa a decisao Estornada por
 * construcao, porque anular credito depende do que foi efetivamente lancado no
 * livro e pertence ao servico de correcao.
 */
class ScoreCalculator
{
    public function __construct(
        private readonly int $bonusPercentual = 20,
        private readonly int $tetoPorLancamento = 500,
    ) {}

    public function calcular(ScoreTransactionData $fato, ?ScoreRuleData $regra): ScoreDecisionData
    {
        // Sem regra publicada nao existe premio: marco desconhecido nao ganha
        // credito generico, o catalogo e a unica fonte de pontuacao.
        if ($regra === null) {
            return $this->zero('regra_nao_publicada');
        }

        if (! $regra->habilitada) {
            return $this->zero('regra_desabilitada');
        }

        // A regra vale pela competencia do fato, nao pela data de execucao do
        // worker. Replay de evento antigo reaplica a versao antiga.
        if (! $regra->vigenteNa($fato->competencia)) {
            return $this->zero('regra_fora_de_vigencia');
        }

        // Autoria e evidencia sao pre-requisitos do credito competitivo. Faltando
        // qualquer uma, o fato fica em apuracao - nunca se atribui ao ultimo
        // usuario conectado nem se presume entrega a partir de updated_at.
        if (! $fato->autoriaComprovada) {
            return $this->apuracao('autoria_nao_comprovada');
        }

        if (! $fato->evidenciaComprovada) {
            return $this->apuracao('source_evidence_missing');
        }

        // Participante fora da competicao (vinculo em apuracao, orgao sem
        // elegibilidade) nao pontua, mas o fato fica registrado.
        if (! $fato->elegivel) {
            return $this->zero('participante_nao_elegivel');
        }

        $base = $regra->pontosBase;
        $bonus = $this->calcularBonus($fato, $regra);

        // Freio de seguranca contra regra mal cadastrada ou adaptador
        // defeituoso. Estouro vai para inspecao humana, jamais vira credito
        // silencioso.
        if ($base + $bonus > $this->tetoPorLancamento) {
            return $this->apuracao('teto_excedido');
        }

        // Marco que exige validacao e ainda nao validado fica pendente: os
        // pontos ja sao conhecidos, mas DecisaoPontuacao::Pendente nao soma ao
        // saldo (ver pontosParaSaldo). Confirmar depois nao recalcula nada.
        if ($regra->exigeValidacao && ! $fato->validada) {
            return new ScoreDecisionData(
                decisao: DecisaoPontuacao::Pendente,
                pontosBase: $base,
                pontosBonus: $bonus,
                motivo: 'aguardando_validacao',
            );
        }

        return new ScoreDecisionData(
            decisao: DecisaoPontuacao::Confirmada,
            pontosBase: $base,
            pontosBonus: $bonus,
            motivo: $bonus > 0 ? 'entrega_tempestiva' : 'entrega_aceita',
        );
    }

    /**
     * Bonus de tempestividade: floor(base * percentual / 100).
     *
     * Divisao inteira TRUNCADA, nao arredondada: base 3 com 20 por cento da
     * zero, nao um. Pontuacao e sempre inteira e nunca arredonda a favor do
     * participante.
     *
     * So incide com prazo aplicavel comprovado E cumprido. Prazo ausente ou
     * desconhecido nao bonifica, e tambem nao reduz a base - a falta do dado e
     * limitacao da fonte, nao falha de quem entregou. Demora do analista nao
     * entra na conta: o que vale e a data de entrega contra o prazo efetivo,
     * ja com eventual prorrogacao aplicada pelo adaptador.
     */
    private function calcularBonus(ScoreTransactionData $fato, ScoreRuleData $regra): int
    {
        if (! $regra->aceitaBonus) {
            return 0;
        }

        if ($fato->prazoEfetivo === null || $fato->entregueEm === null) {
            return 0;
        }

        if ($fato->entregueEm > $fato->prazoEfetivo) {
            return 0;
        }

        return intdiv($regra->pontosBase * $this->bonusPercentual, 100);
    }

    private function zero(string $motivo): ScoreDecisionData
    {
        return new ScoreDecisionData(DecisaoPontuacao::Zero, 0, 0, $motivo);
    }

    private function apuracao(string $motivo): ScoreDecisionData
    {
        return new ScoreDecisionData(DecisaoPontuacao::EmApuracao, 0, 0, $motivo);
    }
}
