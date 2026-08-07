<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Contracts;

/**
 * Contrato unico de guarda de transicao.
 *
 * Uma guarda que nao se aplica ao par de status do contexto deve permitir,
 * nao bloquear. Assim as guardas sao independentes e a ordem de execucao
 * nao importa.
 */
interface GuardaTransicao
{
    public function verificar(ContextoTransicao $contexto): ResultadoGuarda;
}
