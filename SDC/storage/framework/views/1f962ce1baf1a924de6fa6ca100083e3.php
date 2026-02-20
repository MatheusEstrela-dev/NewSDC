<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <title inertia><?php echo e(config('app.name', 'Laravel')); ?></title>

        <!-- Preconnect para recursos externos -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://www.mg.gov.br">

        <!-- Fonts com display=swap para não bloquear renderização -->
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
        <noscript><link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"></noscript>

        <!-- Preload de recursos críticos (apenas em produção) -->
        <?php if(app()->environment('production')): ?>
            <?php
                try {
                    $manifestPath = public_path('build/manifest.json');
                    if (file_exists($manifestPath)) {
                        $manifest = json_decode(file_get_contents($manifestPath), true);
                        $appJs = $manifest['resources/js/app.js']['file'] ?? null;
                        $appCssArray = $manifest['resources/js/app.js']['css'] ?? [];
                        $appCss = !empty($appCssArray) ? $appCssArray[0] : null;

                        if ($appCss) {
                            echo '<link rel="preload" href="/build/' . $appCss . '" as="style">';
                        }
                        if ($appJs) {
                            echo '<link rel="modulepreload" href="/build/' . $appJs . '" as="script">';
                        }
                    }
                } catch (\Exception $e) {
                    // Ignorar erros de manifest em dev
                }
            ?>
        <?php endif; ?>

        <!-- Scripts -->
        <?php echo app('Tighten\Ziggy\BladeRouteGenerator')->generate(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"]); ?>
        <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
    </head>
    <body class="font-sans antialiased">
        <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>
        <script>
            // Force SW cleanup (v3) - unregister old SWs that cache navigation
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(regs) {
                    regs.forEach(function(r) { r.unregister(); });
                });
                caches.keys().then(function(names) {
                    names.forEach(function(name) { caches.delete(name); });
                });
            }
        </script>
    </body>
</html>
<?php /**PATH /var/www/resources/views/app.blade.php ENDPATH**/ ?>