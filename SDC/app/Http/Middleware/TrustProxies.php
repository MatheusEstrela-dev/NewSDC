<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Proxies em que confiamos para ler X-Forwarded-*.
     *
     * ERA '*' -- confiar em QUALQUER origem -- com a justificativa "Azure App
     * Service". O deploy e 100% on-premise desde entao (ver
     * docker/jenkins/Dockerfile:31, "sem Azure CLI"), entao a justificativa
     * caducou e o risco ficou.
     *
     * POR QUE IMPORTA: com '*', quem alcanca o container da aplicacao sem
     * passar pelo Caddy define o proprio IP mandando um X-Forwarded-For. E
     * $request->ip() nao serve so para log -- ele decide seguranca:
     *
     *   AuthenticatedSessionController:85  tooManyAttempts($cpf, $request->ip())
     *   RouteServiceProvider:39            Limit::perMinute(...)->by(... ?: $request->ip())
     *
     * Ou seja: cabecalho forjado zera o contador de tentativas de senha a cada
     * requisicao. Comprovado batendo direto numa replica com tres XFF
     * diferentes -- cada um ganhou balde novo.
     *
     * Em producao isso hoje NAO e alcancavel da internet: nenhum servico alem
     * do Caddy publica porta (stack.app.onpremise.yml), e o Caddy, por padrao,
     * descarta o X-Forwarded-For recebido e escreve o remetente real. Mas essa
     * protecao mora inteira no comportamento do Caddy: no dia em que alguem
     * puser um CDN na frente e configurar `trusted_proxies` la, o Caddy passa a
     * repassar o cabecalho do cliente e o app volta a confiar nele -- com
     * bypass de forca bruta vindo da internet. Nao e um risco que deva depender
     * de uma configuracao distante.
     *
     * O padrao sao as faixas privadas: requisicao que chegue de endereco
     * publico tem o X-Forwarded-* ignorado. Isso NAO protege contra atacante ja
     * dentro da rede privada -- para esse caso o controle e alcancabilidade de
     * rede, nao este arquivo. Ajuste por ambiente com TRUSTED_PROXIES quando
     * souber a faixa exata da overlay.
     */
    protected $proxies;

    public function __construct()
    {
        // De config/app.php, nao de env() direto: com `config:cache` -- que o
        // entrypoint roda no boot -- o .env some do runtime e env() fora de
        // arquivo de config devolve null. O override falharia em silencio.
        $this->proxies = config('app.trusted_proxies');
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
