<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Contracts;

use App\Core\Events\DomainEvent;
use App\Modules\Ranking\DTOs\FatoNormalizado;

/**
 * Traduz o evento de um modulo de origem para o vocabulario do ranking.
 *
 * Um adaptador por dominio. Ele conhece as tabelas e o workflow do proprio
 * modulo; o motor de pontuacao nao conhece nenhum. E aqui que mora todo o
 * conhecimento sujo de cada origem - coluna de autoria com tipo inconsistente,
 * ciclo que muda de nome, prazo que depende de prorrogacao.
 *
 * REGRA DE OURO DO ADAPTADOR: ele nunca inventa prova. Quando a origem nao
 * sustenta a autoria, o ciclo ou o prazo, o adaptador marca o campo como nao
 * comprovado e deixa o fato ir para apuracao. Preencher lacuna com o estado
 * ATUAL do usuario - o orgao de hoje, o ultimo editor, updated_at como data de
 * entrega - produz credito que ninguem consegue auditar depois.
 */
interface ModuleAdapter
{
    /**
     * Este adaptador sabe traduzir este evento?
     *
     * Deve decidir apenas pelo nome/tipo do evento, sem consultar banco: o
     * despachante chama isto em todos os adaptadores registrados.
     */
    public function suporta(DomainEvent $evento): bool;

    /**
     * Traduz o evento. Devolve null quando o evento e reconhecido mas NAO
     * corresponde a um marco premiavel - rascunho salvo, reenvio identico,
     * alteracao cosmetica, mudanca de status que nao fecha ciclo.
     *
     * Null e resultado legitimo e frequente: a maior parte do trafego de um
     * modulo nao e entrega premiavel.
     */
    public function normalizar(DomainEvent $evento): ?FatoNormalizado;

    /**
     * Nome do modulo de origem, como aparece no catalogo de regras (ex.: Rat).
     */
    public function modulo(): string;
}
