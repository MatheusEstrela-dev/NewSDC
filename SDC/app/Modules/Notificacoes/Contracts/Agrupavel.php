<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Contracts;

/**
 * Contrato que uma notificacao precisa cumprir para ser persistida pelo
 * AgrupavelDatabaseChannel.
 *
 * Existe para que o canal nao dependa de uma classe concreta de notificacao: ele
 * so precisa saber a qual modulo a notificacao pertence (para resolver a janela)
 * e qual e o assunto do agrupamento.
 */
interface Agrupavel
{
    /**
     * Slug do modulo em config('notificacoes.modulos'). Define a janela de
     * agrupamento e a preferencia de canal consultada.
     */
    public function modulo(): string;

    /**
     * Assunto do agrupamento, no formato "modulo:identificador". Null entrega a
     * notificacao sem agrupar.
     */
    public function chaveDeAgrupamento(): ?string;

    /**
     * Chamado pelo canal apos a escrita, com o estado real da linha no banco.
     *
     * O contador so e conhecido depois do upsert, e o broadcast precisa dele para
     * o cliente exibir "2 novos" sem refazer a consulta. Como o canal de database
     * roda antes do de broadcast na ordem do via(), o resultado fica disponivel.
     */
    public function registrarPersistencia(string $notificacaoId, int $contadorAgrupado): void;
}
