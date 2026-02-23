<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceRootUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in NativePHP mobile context — the local server runs at 127.0.0.1
        // Detection: check request host, env vars, and OS
        $requestHost = $request->getHost();
        $isMobileLocal = in_array($requestHost, ['127.0.0.1', 'localhost', '0.0.0.0']);
        $isNativePHP = env('NATIVEPHP_RUNNING') || env('NATIVE_PHP');

        if (($isMobileLocal || $isNativePHP) && config('app.url') && app()->environment('local')) {
            // On mobile: force root URL to the actual request origin, not the Jump host
            $scheme = $request->getScheme();
            $port = $request->getPort();
            $localUrl = "{$scheme}://{$requestHost}" . ($port && $port != 80 && $port != 443 ? ":{$port}" : '');
            URL::forceRootUrl($localUrl);
        } elseif (config('app.url') && app()->environment('local')) {
            URL::forceRootUrl(config('app.url'));
        }

        return $next($request);
    }
}
