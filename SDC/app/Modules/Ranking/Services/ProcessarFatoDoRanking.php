<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\ContextoInstitucional;
use App\Modules\Ranking\DTOs\FatoNormalizado;
use App\Modules\Ranking\DTOs\RegraVigente;
use App\Modules\Ranking\DTOs\ScoreDecisionData;
use App\Modules\Ranking\Enums\DecisaoPontuacao;
use App\Modules\Ranking\Models\Transacao;

/**
 * ORQUESTRADOR do ranking: a unica cadeia entre o fato ja traduzido pelo
 * adaptador e a linha gravada no livro de pontos.
 *
 * Nenhuma das quatro etapas conhece a outra, e e de proposito: o resolvedor de
 * contexto nao conhece regra, o calculo nao conhece banco, o livro nao conhece
 * elegibilidade. Quem costura e so esta classe, e por isso ela e o lugar onde
 * as tres decisoes perigosas ficam concentradas e visiveis:
 *
 * 1. O CONTEXTO E O DO INSTANTE DO FATO, NUNCA O DE HOJE
 *    A resolucao usa $fato->ocorridoEm. Usar "agora" creditaria a entrega de
 *    marco ao orgao para o qual o servidor foi transferido em agosto, e o erro
 *    seria invisivel: a linha gravada pareceria correta.
 *
 * 2. ORGAO E MUNICIPIO VEM DO CONTEXTO RESOLVIDO, NUNCA DO PAYLOAD
 *    O evento de origem pode dizer qualquer coisa sobre a quem creditar, e o
 *    adaptador tampouco tem autoridade sobre isso. Aceitar orgao_id do payload
 *    permitiria a quem dispara o evento escolher o beneficiario do ponto.
 *    FatoNormalizado nem carrega esses campos, e esta classe nao os inventa.
 *
 * 3. VINCULO EM APURACAO NAO E "NAO ELEGIVEL"
 *    Sao estados diferentes e nao podem colapsar em Zero. Zero e decisao
 *    terminal ("avaliado, sem direito"); em_apuracao e reversivel ("ainda nao
 *    da para dizer de quem e"). Ver degradarParaApuracao().
 *
 * INTERRUPTOR GERAL
 * Com config('ranking.habilitado') falso o metodo devolve null e NAO grava
 * nada, nem transacao com decisao zero. Modulo desligado nao e modulo que
 * avaliou e recusou: e modulo que nao avaliou.
 *
 * CUIDADO COM OCTANE: stateless. O interruptor e lido UMA vez, na construcao
 * do singleton, e nunca dentro do processamento; usuario, orgao e competencia
 * chegam sempre pelo FatoNormalizado, jamais de Auth ou de request().
 */
class ProcessarFatoDoRanking
{
    /** Motivo registrado quando o vinculo do creditado nao foi reconstruido. */
    public const MOTIVO_VINCULO_EM_APURACAO = 'vinculo_nao_reconstruido';

    private readonly bool $habilitado;

    public function __construct(
        private readonly InstitutionalContextResolver $contextos,
        private readonly RegraVigenteRepository $regras,
        private readonly ScoreCalculator $calculadora,
        private readonly RecordScoreTransaction $livro,

        /**
         * Interruptor do modulo. Nulo cai em config('ranking.habilitado'), o
         * que so acontece na construcao do singleton, nunca a cada fato, para
         * que o comportamento nao mude no meio de um lote em processamento.
         */
        ?bool $habilitado = null,
    ) {
        $this->habilitado = $habilitado ?? (bool) config('ranking.habilitado', false);
    }

    /**
     * Avalia e registra o fato. Devolve a transacao gravada, ou null quando o
     * modulo esta desligado.
     *
     * Idempotencia nao e tratada aqui: ela pertence ao RecordScoreTransaction,
     * por event_id e por (chave_canonica, familia). Reprocessar o mesmo fato
     * devolve a transacao original sem mover saldo.
     */
    public function processar(FatoNormalizado $fato, ?int $geracao = null): ?Transacao
    {
        if (! $this->habilitado) {
            return null;
        }

        $contexto = $this->contextoDoFato($fato);
        $regra = $this->regras->vigenteEm($fato->ruleKey, $fato->competenciaEm);

        // Elegibilidade e do CONTEXTO, nao do adaptador: so vinculo comprovado
        // autoriza credito competitivo. Vinculo inferido existe, serve para
        // auditoria e nao pontua.
        $decisao = $this->calculadora->calcular(
            $fato->paraCalculo($contexto->comprovado()),
            $regra?->regra,
        );

        $decisao = $this->degradarParaApuracao($decisao, $contexto, $regra, $fato);

        return $this->livro->registrar(
            eventId: $fato->eventId,
            eventName: $fato->eventName,
            chaveCanonica: $fato->chaveCanonica,
            familia: $fato->familia,
            modulo: $fato->modulo,
            decisao: $decisao,
            ocorridoEm: $fato->ocorridoEm,
            competenciaEm: $fato->competenciaEm,
            regraId: $regra?->id,
            regraVersao: $regra?->versao,
            actorUserId: $fato->actorUserId,
            creditedUserId: $fato->creditedUserId,
            validadorUserId: $fato->validadorUserId,
            // As duas linhas que nunca vem do payload. Ver ponto 2 do docblock.
            orgaoId: $contexto->orgaoId,
            municipioId: $contexto->municipioId,
            contexto: $this->contextoAuditavel($fato, $contexto, $regra),
            geracao: $geracao,
        );
    }

    /**
     * Contexto institucional do CREDITADO no instante do fato.
     *
     * Sem creditado nao ha vinculo a resolver, e tampouco se procura outro
     * candidato (o executor, o validador). Atribuir o ponto a quem apertou o
     * botao quando o beneficiario e desconhecido e exatamente o tipo de credito
     * que ninguem consegue auditar depois.
     */
    private function contextoDoFato(FatoNormalizado $fato): ContextoInstitucional
    {
        if ($fato->creditedUserId === null) {
            return ContextoInstitucional::emApuracao();
        }

        return $this->contextos->resolver($fato->creditedUserId, $fato->ocorridoEm);
    }

    /**
     * Converte em EmApuracao o Zero que so existe porque o vinculo nao foi
     * reconstruido.
     *
     * O ScoreCalculator recebe apenas o booleano elegivel e, sem vinculo,
     * responde Zero('participante_nao_elegivel'), decisao TERMINAL. Mas "nao
     * sei a que orgao ele pertencia" nao e "ele nao tinha direito": o primeiro
     * se resolve com apuracao e volta a pontuar, o segundo nao. Colapsar os
     * dois fecharia a porta de correcao do caso mais comum de dado historico
     * incompleto.
     *
     * A conversao so ocorre quando a REGRA estava integra (publicada,
     * habilitada e vigente). Se o Zero veio do catalogo - marco desabilitado,
     * fora de vigencia -, ele vale independentemente de vinculo, e apurar
     * vinculo nao mudaria o resultado; nesse caso Zero continua Zero.
     */
    private function degradarParaApuracao(
        ScoreDecisionData $decisao,
        ContextoInstitucional $contexto,
        ?RegraVigente $regra,
        FatoNormalizado $fato,
    ): ScoreDecisionData {
        if ($decisao->decisao !== DecisaoPontuacao::Zero || ! $contexto->emApuracaoPendente()) {
            return $decisao;
        }

        if ($regra === null
            || ! $regra->regra->habilitada
            || ! $regra->regra->vigenteNa($fato->competenciaEm)) {
            return $decisao;
        }

        return new ScoreDecisionData(
            decisao: DecisaoPontuacao::EmApuracao,
            pontosBase: 0,
            pontosBonus: 0,
            motivo: self::MOTIVO_VINCULO_EM_APURACAO,
        );
    }

    /**
     * Contexto gravado em ranking.transacoes.contexto.
     *
     * O bloco `ranking` e escrito POR ULTIMO e sobrescreve chave homonima vinda
     * do adaptador: a evidencia do vinculo e a versao da regra sao afirmacoes
     * deste motor, e um adaptador nao pode forja-las no payload.
     *
     * @return array<string, mixed>
     */
    private function contextoAuditavel(
        FatoNormalizado $fato,
        ContextoInstitucional $contexto,
        ?RegraVigente $regra,
    ): array {
        return array_merge($fato->contexto, [
            'ranking' => [
                'vinculo_evidencia' => $contexto->evidencia,
                'vinculo_orgao_id' => $contexto->orgaoId,
                'vinculo_municipio_id' => $contexto->municipioId,
                'regra_id' => $regra?->id,
                'regra_versao' => $regra?->versao,
                'rule_key' => $fato->ruleKey,
            ],
        ]);
    }
}
