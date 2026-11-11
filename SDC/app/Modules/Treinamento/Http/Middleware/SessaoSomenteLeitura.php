<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Http\Middleware;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abre a sessao so para ler quem esta logado. O player pede um arquivo HLS a
 * cada poucos segundos, em paralelo com a navegacao: se cada pedido regravasse
 * a sessao, um segmento lento poderia sobrescrever o que a pagina acabou de
 * gravar (token CSRF, login) e ainda devolveria Set-Cookie a cada segmento.
 */
class SessaoSomenteLeitura extends StartSession
{
    protected function collectGarbage(Session $session): void {}

    protected function storeCurrentUrl(Request $request, $session): void {}

    protected function addCookieToResponse(Response $response, Session $session): void {}

    protected function saveSession($request): void {}
}
