<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Http\Middleware;

use App\Modules\Tdap\Support\EscopoDeLeitura;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cadastros do TDAP que nao tem municipio unico -- prestadores, frota
 * (caminhoes, vistorias, fotos), atas e lotes -- so o ESTADO altera.
 *
 * Um caminhao ou prestador atende varios municipios. Com so a permissao de
 * rota, um usuario de COMPDEC com `tdap.caminhoes.edit` editava, apagava ou
 * baixava fotos de registros de outros municipios trocando o id na URL
 * (auditoria de 2026-11-05). Leitura continua liberada pela permissao.
 *
 * Bloqueia toda escrita (POST/PUT/PATCH/DELETE) e tambem as telas de
 * formulario (create/edit/nova), para nao abrir uma tela cujo envio falharia.
 */
final class SomenteEstadualAltera
{
    private const TELAS_DE_FORMULARIO = ['.create', '.edit', '.nova'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->altera($request)) {
            EscopoDeLeitura::garantirEstadual($request->user());
        }

        return $next($request);
    }

    private function altera(Request $request): bool
    {
        if (! $request->isMethodSafe()) {
            return true;
        }

        $nome = (string) $request->route()?->getName();

        foreach (self::TELAS_DE_FORMULARIO as $sufixo) {
            if (str_ends_with($nome, $sufixo)) {
                return true;
            }
        }

        return false;
    }
}
