<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le o header X-Client-IP enviado pelo frontend (composable useClientIp:
 * WebRTC + api.ipify) e expoe via $request->attributes->get('client_ip').
 *
 * NAO sobrescreve $request->ip() (que continua sendo a fonte autoritativa
 * server-side, validada por TrustProxies). O valor capturado e usado
 * apenas para auditoria — ex.: User::recordLogin() prioriza o atributo
 * 'client_ip' quando presente, caindo de volta para $request->ip().
 *
 * Justificativa de seguranca: client_ip vem do navegador e pode ser
 * forjado. Por isso e mantido SEPARADO do IP de transporte; usar em
 * autorizacao seria erro.
 */
class ResolveClientIp
{
    private const HEADER = 'X-Client-IP';
    private const MAX_LENGTH = 255;

    /**
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $raw = (string) $request->headers->get(self::HEADER, '');

        if ($raw === '' || strlen($raw) > self::MAX_LENGTH) {
            return $next($request);
        }

        // Pode vir como "ip1, ip2, ip3" (WebRTC LAN + ipify publico).
        // Filtramos para IPs validos e mantemos a ordem original
        // (mais especifico/local primeiro).
        $candidates = array_filter(
            array_map('trim', explode(',', $raw)),
            static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP) !== false,
        );

        if ($candidates === []) {
            return $next($request);
        }

        $request->attributes->set('client_ip', reset($candidates));
        $request->attributes->set('client_ip_all', implode(', ', $candidates));

        return $next($request);
    }
}
