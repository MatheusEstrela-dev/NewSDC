<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Preconnect para recursos externos -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://www.mg.gov.br">

        <!-- Fonts com display=swap para não bloquear renderização -->
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
        <noscript><link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"></noscript>

        <!-- Preload de recursos críticos (apenas em produção) -->
        @if(app()->environment('production'))
            @php
                try {
                    $manifestPath = public_path('build/manifest.json');
                    if (file_exists($manifestPath)) {
                        $manifest = json_decode(file_get_contents($manifestPath), true);
                        $appCss = $manifest['resources/css/app.css']['file'] ?? null;
                        $appJs = $manifest['resources/js/app.js']['file'] ?? null;
                        
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
            @endphp
        @endif

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
