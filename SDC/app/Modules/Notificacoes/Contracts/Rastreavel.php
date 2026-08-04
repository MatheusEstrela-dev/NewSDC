<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Contracts;

/**
 * O que um protocolo declara para ter trilha de acoes no sino do dono.
 *
 * A intencao e que o modulo DECLARE, nunca implemente: aqui nao entra montagem de
 * spec, resolucao de preferencia, agrupamento ou texto. Antes desta interface, RAT,
 * Demandas e PAE tinham cada um a sua copia da mesma regra ("quem e o dono", "nao
 * avisar o autor", "qual URL", "agrupa ou nao") em observers e services proprios, e
 * os outros modulos simplesmente nao tinham gatilho nenhum.
 *
 * O trait TrilhaDeAcoes ja responde por tudo que tem default razoavel, entao na
 * pratica um modulo novo implementa tres metodos: modulo, rotulo e donos.
 */
interface Rastreavel
{
    /**
     * Slug de config('notificacoes.modulos'). Decide a preferencia do usuario
     * (ele pode desligar o modulo no sino) e a janela de agrupamento.
     */
    public function moduloNotificacao(): string;

    /**
     * Como o protocolo se chama para o usuario final. Ex.: "RAT 2026-000000007-001".
     *
     * NUNCA devolver chave interna. Antes, o RAT caia em "#{id}" quando o numero
     * estava vazio e a notificacao chegava como "A ocorrencia
     * #019eef2b-ee60-7108-8ecd-ba91d21fe9ea foi finalizada", que o usuario le como
     * erro do sistema. Sem numero, o rotulo deve ser algo que ele reconheca --
     * tipicamente o tipo mais a data de abertura.
     */
    public function rotuloProtocolo(): string;

    /**
     * Nome curto do tipo de protocolo, usado no titulo do card ("RAT atualizado").
     */
    public function nomeCurtoNotificacao(): string;

    /**
     * Destino do botao de acao do card. Null remove o botao, em vez de gerar um
     * link morto.
     */
    public function urlNotificacao(): ?string;

    /**
     * Rotulo do botao de acao ("Abrir RAT").
     */
    public function acaoTextoNotificacao(): string;

    /**
     * Ids de usuario que acompanham este protocolo: criador e responsavel atual.
     *
     * Quem executou a acao e descartado depois, pelo servico -- aqui devolve-se a
     * lista completa, sem se preocupar com isso.
     *
     * @return list<int>
     */
    public function donosNotificacao(): array;

    /**
     * Coluna que representa a situacao do protocolo, ou null quando o modulo ja
     * tem gatilho proprio de status e nao quer aviso duplicado.
     */
    public function campoSituacao(): ?string;

    /**
     * Situacao atual em texto ("Finalizado"). Usada no titulo e na frase do card.
     */
    public function rotuloSituacao(): ?string;

    /**
     * Complemento opcional da frase de situacao, para consequencia que o usuario
     * precisa saber ("e nao aceita mais edicao").
     */
    public function detalheSituacao(): ?string;

    /**
     * Tipo do card na virada de situacao, entre os de NotificacaoSpec::TIPOS.
     *
     * So o modulo sabe se a situacao nova e boa noticia ("Finalizado" -> success) ou
     * problema ("Indeferido" -> warning). Null aceita o default da acao.
     */
    public function tipoSituacaoNotificacao(): ?string;

    /**
     * Colunas cuja alteracao NAO conta como edicao do protocolo.
     *
     * Sem isso, qualquer toque de infraestrutura (timestamp, contador denormalizado,
     * cache de totais) viraria card no sino e o usuario aprenderia a ignorar o sino.
     *
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array;
}
