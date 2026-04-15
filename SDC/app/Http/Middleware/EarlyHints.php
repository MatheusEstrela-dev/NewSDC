<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para enviar Early Hints (HTTP 103) com FrankenPHP.
 *
 * Envia headers Link para CSS/JS criticos ANTES do HTML terminar de processar,
 * permitindo que o navegador comece a baixar assets enquanto o servidor
 * ainda esta gerando a resposta.
 *
 * Funciona com RoadRunner tambem (apenas adiciona Link headers sem 103).
 */
class EarlyHints
{
    private static ?array $manifest = null;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->shouldSendEarlyHints($request)) {
            return $next($request);
        }

        $this->sendEarlyHints();

        $response = $next($request);

        $this->addLinkHeaders($response);

        return $response;
    }

    private function shouldSendEarlyHints(Request $request): bool
    {
        if (!$request->isMethod('GET')) {
            return false;
        }

        if ($request->is('api/*', 'build/*', 'storage/*', 'imgs/*', 'favicon.ico')) {
            return false;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }

        $accept = $request->header('Accept', '');
        return str_contains($accept, 'text/html') || str_contains($accept, '*/*');
    }

    private function sendEarlyHints(): void
    {
        if (!$this->isFrankenPHP()) {
            return;
        }

        $manifest = $this->getManifest();
        if (empty($manifest)) {
            return;
        }

        foreach ($this->getCriticalAssets($manifest) as $asset) {
            $header = $this->buildLinkHeader($asset);
            header($header, false);
        }

        if (function_exists('headers_send')) {
            headers_send(103);
        }
    }

    private function addLinkHeaders(Response $response): void
    {
        $manifest = $this->getManifest();
        if (empty($manifest)) {
            return;
        }

        $links = [];
        foreach ($this->getCriticalAssets($manifest) as $asset) {
            $links[] = $this->buildLinkValue($asset);
        }

        if (empty($links)) {
            return;
        }

        $existing = $response->headers->get('Link', '');
        $newLinks = implode(', ', $links);

        $response->headers->set(
            'Link',
            $existing ? "{$existing}, {$newLinks}" : $newLinks
        );
    }

    private function isFrankenPHP(): bool
    {
        return config('octane.server') === 'frankenphp'
            && function_exists('headers_send');
    }

    private function getManifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $manifestPath = public_path('build/manifest.json');

        if (!file_exists($manifestPath)) {
            self::$manifest = [];
            return self::$manifest;
        }

        try {
            $content = file_get_contents($manifestPath);
            self::$manifest = json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            report($e);
            self::$manifest = [];
        }

        return self::$manifest;
    }

    private function getCriticalAssets(array $manifest): array
    {
        $assets = [];

        $appEntry = $manifest['resources/js/app.js'] ?? [];

        if (!empty($appEntry['css'])) {
            foreach ($appEntry['css'] as $css) {
                $assets[] = [
                    'url' => "/build/{$css}",
                    'as' => 'style',
                    'type' => 'text/css',
                ];
            }
        }

        if (!empty($appEntry['file'])) {
            $assets[] = [
                'url' => "/build/{$appEntry['file']}",
                'rel' => 'modulepreload',
                'crossorigin' => 'anonymous',
            ];
        }

        $vendorChunks = ['vendor-vue', 'vendor-icons', 'vendor-query'];
        foreach ($manifest as $key => $entry) {
            if (is_array($entry) && isset($entry['file'])) {
                foreach ($vendorChunks as $chunk) {
                    if (str_contains($entry['file'], $chunk)) {
                        $assets[] = [
                            'url' => "/build/{$entry['file']}",
                            'rel' => 'modulepreload',
                            'crossorigin' => 'anonymous',
                        ];
                        break;
                    }
                }
            }
        }

        $assets[] = [
            'url' => 'https://fonts.bunny.net',
            'rel' => 'preconnect',
            'crossorigin' => 'anonymous',
        ];

        return $assets;
    }

    private function buildLinkHeader(array $asset): string
    {
        return 'Link: ' . $this->buildLinkValue($asset);
    }

    private function buildLinkValue(array $asset): string
    {
        $parts = ['<' . $asset['url'] . '>'];

        $rel = $asset['rel'] ?? 'preload';
        $parts[] = "rel={$rel}";

        if (isset($asset['as'])) {
            $parts[] = "as={$asset['as']}";
        }

        if (isset($asset['type'])) {
            $parts[] = "type={$asset['type']}";
        }

        if (isset($asset['crossorigin'])) {
            $parts[] = "crossorigin={$asset['crossorigin']}";
        }

        return implode('; ', $parts);
    }
}
