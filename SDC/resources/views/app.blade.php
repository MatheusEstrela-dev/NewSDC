<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Chave publica do Reverb, entregue em TEMPO DE REQUISICAO.

             Nao pode vir de import.meta.env: as VITE_* sao assadas no bundle
             durante `bun run build`, e o build roda dentro da imagem Docker, onde
             o .env nao existe (esta no .dockerignore). O resultado era a chave
             virar undefined, o Echo nunca inicializar -- em silencio, por desenho
             do `if (!key)` no bootstrap.js -- e cada aba cair do polling de 5min
             para o de 30s. Dez vezes mais carga de base, em producao, sem aviso.

             Entregando por aqui, a MESMA imagem serve homologacao e producao com
             chaves diferentes. Assar no bundle obrigaria uma imagem por ambiente,
             que e o oposto de promover artefato entre ambientes.

             E a chave PUBLICA, a mesma que o navegador recebe para abrir o
             websocket. O REVERB_APP_SECRET nao aparece aqui e nunca deve: ele
             assina eventos no servidor. --}}
        <meta name="reverb-key" content="{{ config('broadcasting.default') === 'reverb' ? config('broadcasting.connections.reverb.key') : '' }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Preconnect para recursos externos -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.bunny.net">

        <!-- Fonts com display=swap para legibilidade imediata (fallback system-ui ate Inter carregar) -->
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
        <noscript><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"></noscript>

        <!-- Scripts -->
        @routes
        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
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
