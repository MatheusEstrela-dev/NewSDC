<?php

declare(strict_types=1);

namespace App\Support\Database\Log;

/**
 * Reacao a um lote apagado pelo PodadorDeLogs, declarada em config/retencao.php
 * na chave 'apos' da tabela.
 *
 * Existe para quem guarda estado derivado das linhas podadas (cache, contador)
 * sem que o podador conheca o dominio. Chamada depois do commit do lote.
 */
interface AposPoda
{
    /**
     * @param  list<object>  $linhas  colunas de 'retornar' das linhas apagadas no lote
     */
    public function aposPodar(array $linhas): void;
}
