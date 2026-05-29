<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\View;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Renderer com fallback automatico:
 *   1) Tenta Vue SSR via Bun (mais rapido em cold-start)
 *   2) Tenta Vue SSR via Node
 *   3) Fallback pra view Blade espelhada (emails.{component_underscore})
 *
 * Isso permite ambientes sem JS runtime no container (queue worker) operar
 * com Blade enquanto produc oes com Bun/Node usam Vue SSR Atomic.
 *
 * O bundle JS e produzido por `npm run build:email-ssr` e fica em
 * bootstrap/ssr/email-ssr.js — quando ausente OU runtime ausente,
 * cai automaticamente no Blade.
 */
class VueEmailRenderer
{
    private const PROCESS_TIMEOUT_SECONDS = 15;

    private const COMPONENT_TO_BLADE = [
        'EmailChangeVerification' => 'emails.email_change_verification',
        'EmailChangeNotice'       => 'emails.email_change_notice',
    ];

    public function render(string $component, array $props): string
    {
        // Tentar SSR (Bun -> Node) se runtime + bundle estiverem disponiveis.
        $html = $this->tryVueSsr($component, $props);
        if ($html !== null) {
            return $html;
        }

        // Fallback Blade
        return $this->renderBlade($component, $props);
    }

    private function tryVueSsr(string $component, array $props): ?string
    {
        $bundlePath = base_path('bootstrap/ssr/email-ssr.js');
        if (!is_file($bundlePath)) {
            return null;
        }

        $runtime = $this->findJsRuntime();
        if ($runtime === null) {
            return null;
        }

        try {
            $propsJson = json_encode($props, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }

        $process = new Process([$runtime, $bundlePath, $component, $propsJson]);
        $process->setTimeout(self::PROCESS_TIMEOUT_SECONDS);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        $output = $process->getOutput();
        if ($output === '') {
            return null;
        }

        // Vue SSR nao emite doctype (nao cabe como sibling do <html> root).
        return "<!DOCTYPE html>\n" . $output;
    }

    private function findJsRuntime(): ?string
    {
        $finder = new ExecutableFinder();
        // Bun preferido (cold-start ~10x mais rapido que Node)
        return $finder->find('bun') ?? $finder->find('node');
    }

    private function renderBlade(string $component, array $props): string
    {
        $view = self::COMPONENT_TO_BLADE[$component] ?? null;
        if ($view === null || !View::exists($view)) {
            throw new RuntimeException(
                "Email render fallback failed: no Blade view registered for component '{$component}'."
            );
        }

        return View::make($view, $this->normalizePropsForBlade($component, $props))->render();
    }

    /**
     * Vue SSR passa props como JSON com strings/ints; Blade espera Carbon e
     * outros formatos em alguns campos. Normaliza apenas os campos que diferem.
     */
    private function normalizePropsForBlade(string $component, array $props): array
    {
        if ($component === 'EmailChangeVerification' && isset($props['expiresAt']) && is_string($props['expiresAt'])) {
            try {
                $props['expiresAt'] = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $props['expiresAt']);
            } catch (\Throwable $e) {
                $props['expiresAt'] = \Carbon\Carbon::now()->addMinutes(15);
            }
        }
        return $props;
    }
}
