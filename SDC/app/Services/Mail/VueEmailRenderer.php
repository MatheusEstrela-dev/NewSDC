<?php

namespace App\Services\Mail;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Renders Vue email components to HTML via the Node SSR bundle.
 *
 * The bundle is built by `npm run build:email-ssr` and lives at
 * bootstrap/ssr/email-ssr.js. This service shells out to Node to
 * render a single email — synchronous, short-lived process suitable
 * for queue workers.
 *
 * If the bundle is missing (build step skipped), throws a clear
 * RuntimeException pointing at the build command.
 */
class VueEmailRenderer
{
    private const PROCESS_TIMEOUT_SECONDS = 15;

    public function render(string $component, array $props): string
    {
        $bundlePath = base_path('bootstrap/ssr/email-ssr.js');

        if (!is_file($bundlePath)) {
            throw new RuntimeException(
                "Email SSR bundle not found at {$bundlePath}. "
                . "Run `npm run build:email-ssr` (or `npm run build:ssr`)."
            );
        }

        $propsJson = json_encode($props, JSON_THROW_ON_ERROR);

        $process = new Process([
            'node',
            $bundlePath,
            $component,
            $propsJson,
        ]);
        $process->setTimeout(self::PROCESS_TIMEOUT_SECONDS);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                "Email SSR render failed for {$component}: "
                . $process->getErrorOutput()
            );
        }

        $html = $process->getOutput();
        if ($html === '') {
            throw new RuntimeException(
                "Email SSR render returned empty output for {$component}."
            );
        }

        // Vue SSR nao emite doctype (nao cabe como sibling do <html> root).
        // Prepend para strict HTML5 clients (Outlook, etc.).
        return "<!DOCTYPE html>\n" . $html;
    }
}
