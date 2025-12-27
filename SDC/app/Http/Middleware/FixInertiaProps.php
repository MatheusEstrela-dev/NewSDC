<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

/**
 * Middleware temporário para corrigir props do Inertia
 * Remove quando o cache PHP for limpo
 */
class FixInertiaProps
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Apenas processar respostas Inertia
        if ($response instanceof \Illuminate\Http\Response) {
            $content = $response->getContent();

            // Se for uma resposta Inertia (HTML com div#app)
            if (str_contains($content, 'id="app"') && str_contains($content, 'data-page')) {
                // Extrair o JSON do data-page
                if (preg_match('/data-page="([^"]+)"/', $content, $matches)) {
                    $pageData = json_decode(html_entity_decode($matches[1]), true);

                    if (isset($pageData['props'])) {
                        $props = &$pageData['props'];

                        // Corrigir movimentacoes se for um Paginator
                        if (isset($props['movimentacoes']) &&
                            is_array($props['movimentacoes']) &&
                            isset($props['movimentacoes']['data'])) {
                            $props['movimentacoes'] = $props['movimentacoes']['data'];
                        }

                        // Corrigir recebimentos se for um Paginator
                        if (isset($props['recebimentos']) &&
                            is_array($props['recebimentos']) &&
                            isset($props['recebimentos']['data'])) {
                            $props['recebimentos'] = $props['recebimentos']['data'];
                        }

                        // Corrigir products se for um Paginator
                        if (isset($props['products']) &&
                            is_array($props['products']) &&
                            isset($props['products']['data'])) {
                            $props['products'] = $props['products']['data'];
                        }

                        // Substituir no HTML
                        $newPageData = htmlspecialchars(json_encode($pageData), ENT_QUOTES, 'UTF-8');
                        $content = preg_replace(
                            '/data-page="[^"]+"/',
                            'data-page="' . $newPageData . '"',
                            $content
                        );

                        $response->setContent($content);
                    }
                }
            }
        }

        return $response;
    }
}
