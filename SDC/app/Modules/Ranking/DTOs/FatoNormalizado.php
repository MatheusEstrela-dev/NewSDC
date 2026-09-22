<?php

declare(strict_types=1);

namespace App\Modules\Ranking\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Saida de um adaptador de modulo: o evento de origem ja traduzido para o
 * vocabulario do ranking, sem nenhuma decisao de pontuacao tomada.
 *
 * O adaptador responde "o que aconteceu e quem provou", nunca "quanto vale".
 * O valor sai do catalogo de regras, e a decisao sai do ScoreCalculator.
 *
 * Os tres booleanos de prova sao o coracao do contrato. O adaptador so os marca
 * como verdadeiros quando a EVIDENCIA DA ORIGEM sustenta a afirmacao - nao por
 * conveniencia, nem para "nao perder" o ponto. No RAT, por exemplo, varias
 * tabelas guardam created_by como texto livre e outras como bigint, nenhuma com
 * FK para users: onde a autoria nao for reconstruivel com seguranca, o correto
 * e autoriaComprovada = false, que leva o fato para apuracao.
 */
final readonly class FatoNormalizado
{
    public function __construct(
        /** UUID do DomainEvent de origem. Barreira tecnica de idempotencia. */
        public string $eventId,

        /** Nome do evento, ex.: rat.relatorio.finalizado */
        public string $eventName,

        /** Modulo de origem, ex.: Rat. Nunca o literal reservado 'all'. */
        public string $modulo,

        /**
         * Identidade do FATO no negocio, estavel entre execucoes e entre
         * modulos: recurso + ciclo + marco. Dois eventos diferentes que
         * descrevem a mesma entrega precisam produzir a MESMA chave, senao a
         * barreira de negocio nao segura o premio duplo.
         */
        public string $chaveCanonica,

        /**
         * Familia da regra, do catalogo em RankingRegraSeeder. Compartilhada
         * entre modulos que disputam o mesmo premio, como Compdec e Cedec em
         * cadastro_institucional_validacao.
         */
        public string $familia,

        /** rule_key do catalogo, usada para localizar a regra vigente. */
        public string $ruleKey,

        public DateTimeImmutable $ocorridoEm,

        /**
         * Periodo ao qual a entrega pertence. Entrega aceita com atraso pontua
         * na competencia da entrega valida original, nao na data da aceitacao.
         */
        public DateTimeImmutable $competenciaEm,

        /** Provas. Ver docblock da classe: so true com evidencia da origem. */
        public bool $autoriaComprovada,
        public bool $evidenciaComprovada,
        public bool $validada,

        /** Quem executou a acao. Pode diferir de quem recebe o credito. */
        public ?int $actorUserId = null,

        /**
         * Quem recebe o ponto. Em treinamento e o participante, nao o instrutor
         * que registrou a conclusao. Nulo leva o fato para apuracao.
         */
        public ?int $creditedUserId = null,

        /** Quem validou. NAO herda o premio; pode gerar marco proprio. */
        public ?int $validadorUserId = null,

        /**
         * Prazo ja com prorrogacao aplicada, e data de entrega. Andam em par:
         * um sem o outro nao bonifica. Ausentes nao penalizam a base.
         */
        public ?DateTimeImmutable $prazoEfetivo = null,
        public ?DateTimeImmutable $entregueEm = null,

        /** Evidencia auxiliar para auditoria. Sem dado pessoal desnecessario. */
        public array $contexto = [],
    ) {
        foreach (['eventId' => $eventId, 'eventName' => $eventName, 'modulo' => $modulo,
                  'chaveCanonica' => $chaveCanonica, 'familia' => $familia, 'ruleKey' => $ruleKey] as $campo => $valor) {
            if (trim($valor) === '') {
                throw new InvalidArgumentException("FatoNormalizado exige {$campo} nao vazio.");
            }
        }

        if (strcasecmp($modulo, 'all') === 0) {
            throw new InvalidArgumentException("'all' e rotulo reservado da linha de total em saldos.");
        }

        if ($autoriaComprovada && $creditedUserId === null) {
            throw new InvalidArgumentException('Autoria comprovada exige creditedUserId.');
        }

        foreach (['actorUserId' => $actorUserId, 'creditedUserId' => $creditedUserId,
                  'validadorUserId' => $validadorUserId] as $campo => $id) {
            if ($id !== null && $id <= 0) {
                throw new InvalidArgumentException("{$campo} deve ser positivo quando informado.");
            }
        }
    }

    /**
     * Projecao para o calculo. O contexto institucional entra depois, pelo
     * resolvedor: elegibilidade nao e o adaptador quem decide.
     */
    public function paraCalculo(bool $elegivel): ScoreTransactionData
    {
        return new ScoreTransactionData(
            competencia: $this->competenciaEm,
            autoriaComprovada: $this->autoriaComprovada,
            evidenciaComprovada: $this->evidenciaComprovada,
            elegivel: $elegivel,
            validada: $this->validada,
            entregueEm: $this->entregueEm,
            prazoEfetivo: $this->prazoEfetivo,
        );
    }
}
