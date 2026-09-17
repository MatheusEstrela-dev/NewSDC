<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Illuminate\Http\Request;

/**
 * Politica unica de amostragem do log de auditoria.
 *
 * Existe porque LogApiRequests e LogSystemActivity decidiam a mesma coisa com
 * regras diferentes: um amostrava TODO 2xx (mutacoes inclusive), o outro so
 * leituras. Com os dois ativos o resultado era incoerente, e qualquer ajuste
 * de volume precisava ser feito em dois lugares com semanticas distintas.
 *
 * A regra, em ordem:
 *  - endpoint de infra (health/metrics): nunca. E ping, nao evento de negocio,
 *    e o load balancer martela /health;
 *  - status >= 400: SEMPRE. O que falhou nunca e amostrado;
 *  - metodo diferente de GET: SEMPRE. Mutacao e o proprio sinal de auditoria
 *    ("quem alterou o que") e nao pode ser perdida por sorteio;
 *  - o resto (leitura bem-sucedida): amostrado.
 *
 * Ou seja: o percentual so mexe no ruido. O sinal de auditoria e integral em
 * qualquer configuracao, inclusive na mais agressiva.
 */
final class AmostragemDeAuditoria
{
    public static function deveRegistrar(Request $request, int $status): bool
    {
        if (self::endpointDeInfra($request)) {
            return false;
        }

        if ($status >= 400 || ! $request->isMethod('GET')) {
            return true;
        }

        $percentual = (int) config('logging.activity_sample_percent', 100);

        if ($percentual >= 100) {
            return true;
        }

        if ($percentual <= 0) {
            return false;
        }

        return random_int(1, 100) <= $percentual;
    }

    /**
     * Endpoints de monitoramento/infra que nao devem poluir a auditoria.
     */
    public static function endpointDeInfra(Request $request): bool
    {
        return $request->is(
            'health',
            'api/health',
            'api/health/*',
            'metrics',
            'api/metrics',
        );
    }
}
