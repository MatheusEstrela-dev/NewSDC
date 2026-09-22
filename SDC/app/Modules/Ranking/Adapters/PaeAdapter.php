<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;
use DateTimeImmutable;
use Throwable;

/**
 * Traducao dos Domain Events do PAE para o vocabulario do ranking.
 *
 * O PAE e o fluxo de Plano de Acao de Emergencia de BARRAGEM: protocolo do
 * empreendedor, ficha tecnica, ciclos de notificacao e parecer do analista.
 * Nada aqui pertence a familia de plano municipal -- PAE de empreendimento NAO
 * substitui PlanCon, e por isso nenhuma familia plano_municipal_* aparece no
 * mapa abaixo.
 *
 * As quatro chaves sao exatamente as do RankingRegraSeeder. Alterar uma string
 * aqui sem alterar o catalogo desliga silenciosamente o premio.
 *
 * REGRA DE OURO (ver ModuleAdapter): o adaptador nao inventa prova.
 *  - sem `credited_user_id` -> autoriaComprovada = false e o fato vai a apuracao;
 *  - sem data real de marco (`entregue_em`) -> evidenciaComprovada = false.
 *    updated_at NAO e prova de entrega e por isso nunca chega ate aqui.
 *  - em pae.formulario.validado o creditado e o AUTOR da ficha; o analista
 *    entra como validador e NUNCA e promovido a creditado por fallback.
 */
final class PaeAdapter implements ModuleAdapter
{
    private const MODULO = 'Pae';

    /**
     * eventName => configuracao do marco.
     *
     *  recurso        prefixo e id usados na chave canonica (recurso + ciclo + marco)
     *  chave_id       chave do metadata que identifica o recurso
     *  credita_actor  o executor e, por definicao do marco, o creditado
     *  validada       o marco passou por ato de validacao de terceiro
     *
     * @var array<string, array<string, mixed>>
     */
    private const MARCOS = [
        'pae.protocolo.enviado' => [
            'rule_key'      => 'pae.protocolo_enviado',
            'familia'       => 'pae_protocolo_enviado',
            'recurso'       => 'protocolo',
            'chave_id'      => 'protocolo_id',
            'marco'         => 'enviado',
            'credita_actor' => true,
            'validada'      => false,
        ],
        'pae.formulario.validado' => [
            'rule_key'      => 'pae.formulario_validado',
            'familia'       => 'pae_formulario_validado',
            'recurso'       => 'formulario',
            'chave_id'      => 'formulario_id',
            'marco'         => 'validado',
            // O executor da finalizacao pode ser o analista. Creditar o actor
            // por fallback inverteria autor e validador -- o erro que este
            // adaptador existe para impedir.
            'credita_actor' => false,
            'validada'      => true,
        ],
        'pae.revisao.aceita' => [
            'rule_key'      => 'pae.revisao_aceita',
            'familia'       => 'pae_revisao_aceita',
            'recurso'       => 'notificacao',
            'chave_id'      => 'notificacao_id',
            'marco'         => 'revisao-aceita',
            'credita_actor' => true,
            'validada'      => true,
        ],
        'pae.parecer.concluido' => [
            'rule_key'      => 'pae.parecer_concluido',
            'familia'       => 'pae_parecer_concluido',
            'recurso'       => 'protocolo',
            'chave_id'      => 'protocolo_id',
            'marco'         => 'parecer-concluido',
            // O parecer e ato proprio do analista: ele mesmo recebe o ponto.
            'credita_actor' => true,
            'validada'      => false,
        ],
    ];

    /**
     * Trafego reconhecido que NAO e entrega premiavel. Reenvio identico, anexo
     * e retoque de texto passam pelos mesmos services e gerariam premio duplo
     * sobre a mesma entrega.
     */
    private const ALTERACOES_NAO_PREMIAVEIS = [
        'anexo',
        'anexo_adicionado',
        'anexo_removido',
        'cosmetica',
        'cosmetico',
        'rascunho',
        'reenvio',
        'reenvio_identico',
    ];

    public function suporta(DomainEvent $evento): bool
    {
        return array_key_exists($evento->eventName(), self::MARCOS);
    }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        if (! $this->suporta($evento)) {
            return null;
        }

        $marco = self::MARCOS[$evento->eventName()];
        $meta = $evento->metadata;

        if ($this->naoPremiavel($meta)) {
            return null;
        }

        // Sem o id do recurso nao ha chave canonica estavel, e sem chave
        // estavel a barreira de negocio contra premio duplo nao segura nada.
        $recursoId = $this->inteiroPositivo($meta[$marco['chave_id']] ?? null);
        if ($recursoId === null) {
            return null;
        }

        $ciclo = $this->inteiroPositivo($meta['ciclo'] ?? null) ?? 1;
        $actor = $this->inteiroPositivo($meta['actor_user_id'] ?? null);
        $validador = $this->inteiroPositivo($meta['validador_user_id'] ?? null);

        $creditado = $this->inteiroPositivo($meta['credited_user_id'] ?? null);
        if ($creditado === null && $marco['credita_actor'] === true) {
            $creditado = $actor;
        }

        // Guarda dura contra a inversao de papeis: no formulario validado o
        // ponto e do autor. Se a unica autoria disponivel for a do validador,
        // o fato vai a apuracao em vez de premiar quem validou.
        if ($marco['credita_actor'] === false && $creditado !== null && $creditado === $validador) {
            $creditado = null;
        }

        $entregueEm = $this->data($meta['entregue_em'] ?? null);
        $prazoEfetivo = $this->data($meta['prazo_em'] ?? null);

        return new FatoNormalizado(
            eventId:             $evento->eventId,
            eventName:           $evento->eventName(),
            modulo:              self::MODULO,
            chaveCanonica:       $this->chaveCanonica($marco, $recursoId, $ciclo),
            familia:             $marco['familia'],
            ruleKey:             $marco['rule_key'],
            ocorridoEm:          $evento->occurredAt,
            // Entrega aceita com atraso pontua na competencia da entrega real,
            // nao na data em que o evento foi processado.
            competenciaEm:       $entregueEm ?? $evento->occurredAt,
            autoriaComprovada:   $creditado !== null,
            evidenciaComprovada: $entregueEm !== null,
            validada:            (bool) $marco['validada'],
            actorUserId:         $actor,
            creditedUserId:      $creditado,
            validadorUserId:     $validador,
            prazoEfetivo:        $prazoEfetivo,
            entregueEm:          $entregueEm,
            contexto:            $this->contexto($meta, $ciclo),
        );
    }

    public function modulo(): string
    {
        return self::MODULO;
    }

    /**
     * recurso + ciclo + marco. Deriva so de identificadores do negocio -- nunca
     * do eventId nem do instante -- para que a reemissao do mesmo marco produza
     * a MESMA chave.
     *
     * @param  array<string, mixed>  $marco
     */
    private function chaveCanonica(array $marco, int $recursoId, int $ciclo): string
    {
        return sprintf('pae:%s:%d:c%d:%s', $marco['recurso'], $recursoId, $ciclo, $marco['marco']);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function naoPremiavel(array $meta): bool
    {
        if (filter_var($meta['reenvio_identico'] ?? false, FILTER_VALIDATE_BOOL)) {
            return true;
        }

        $tipo = mb_strtolower(trim((string) ($meta['tipo_alteracao'] ?? '')));

        return $tipo !== '' && in_array($tipo, self::ALTERACOES_NAO_PREMIAVEIS, true);
    }

    /**
     * Evidencia de auditoria, sem dado pessoal alem dos ids ja carregados nos
     * campos proprios do DTO.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function contexto(array $meta, int $ciclo): array
    {
        return array_filter([
            'ciclo'             => $ciclo,
            'protocolo_id'      => $this->inteiroPositivo($meta['protocolo_id'] ?? null),
            'num_protocolo'     => isset($meta['num_protocolo']) ? (string) $meta['num_protocolo'] : null,
            'formulario_id'     => $this->inteiroPositivo($meta['formulario_id'] ?? null),
            'notificacao_id'    => $this->inteiroPositivo($meta['notificacao_id'] ?? null),
            'num_sei'           => isset($meta['num_sei']) ? (string) $meta['num_sei'] : null,
            'decisao'           => isset($meta['decisao']) ? (string) $meta['decisao'] : null,
            'origem'            => isset($meta['origem']) ? (string) $meta['origem'] : null,
        ], static fn ($valor): bool => $valor !== null && $valor !== '');
    }

    private function inteiroPositivo(mixed $valor): ?int
    {
        if ($valor === null || $valor === '' || is_bool($valor) || is_array($valor)) {
            return null;
        }

        if (! is_numeric($valor)) {
            return null;
        }

        $inteiro = (int) $valor;

        return $inteiro > 0 ? $inteiro : null;
    }

    /**
     * Converte data de marco vinda do metadata. String invalida devolve null e
     * o fato perde a evidencia -- nunca vira excecao nem data de hoje.
     */
    private function data(mixed $valor): ?DateTimeImmutable
    {
        if ($valor instanceof DateTimeImmutable) {
            return $valor;
        }

        if ($valor instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($valor);
        }

        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($valor);
        } catch (Throwable) {
            return null;
        }
    }
}
