<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

/**
 * Ciclo do protocolo PAE = o sufixo -VVV de num_protocolo (dd.mm.aaaa-NNNN-VVV).
 *
 * Cada revisao do mesmo PAE nasce como uma versao nova do protocolo
 * (PaeProtocoloService::relacionar), entao o sufixo E o numero do ciclo. Numero
 * fora do padrao cai em 1 -- o ciclo entra na chave canonica do ranking e nao
 * pode ficar indefinido.
 *
 * Existe como classe propria porque tres services (protocolo, formulario e
 * notificacao) precisam da mesma leitura; duplicar o regex criaria tres
 * definicoes de "que ciclo e este".
 */
final class CicloProtocolo
{
    public static function de(?string $numProtocolo): int
    {
        if ($numProtocolo !== null && preg_match('/-(\d{3})$/', $numProtocolo, $m) === 1) {
            return max(1, (int) $m[1]);
        }

        return 1;
    }
}
