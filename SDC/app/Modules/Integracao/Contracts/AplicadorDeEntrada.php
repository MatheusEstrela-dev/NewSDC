<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Contracts;

use App\Modules\Integracao\Exceptions\EntradaJaAplicadaException;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use Illuminate\Http\Request;

/**
 * Aplica uma operacao da API externa recebida pela caixa de entrada.
 *
 * Uma implementacao por operacao (config/integracao.php). Chama o mesmo
 * service de dominio do controller sincrono: a regra de negocio nao e
 * duplicada aqui, so o mapeamento payload -> DTO -> service.
 */
interface AplicadorDeEntrada
{
    /**
     * Regras de validacao da borda: as mesmas do FormRequest/validate do
     * endpoint sincrono. Rodam no recebimento (422 na hora) e de novo na
     * aplicacao (exists/unique tardios).
     *
     * @return array<string, mixed>
     */
    public function regras(Request $request): array;

    /**
     * Chave de serializacao: mensagens com a mesma chave sao aplicadas uma
     * de cada vez, na ordem de chegada (ex.: rat:<id>, pae:protocolo:<id>).
     *
     * @param  array<string, mixed>  $payload  dados ja validados
     * @param  array<string, mixed>  $parametrosRota
     */
    public function recursoChave(array $payload, array $parametrosRota): string;

    /**
     * Aplica no sdc. Roda dentro da transacao do sdc aberta pelo job, com
     * Auth::setUser() do dono do token ja feito.
     *
     * @return array<string, mixed> resultado exposto no status (ex.: id gerado)
     *
     * @throws EntradaJaAplicadaException quando o efeito ja existe (ex.: unique, devolutiva registrada)
     */
    public function aplicar(MensagemDeEntrada $mensagem): array;
}
