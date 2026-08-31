{{--
    Rate limit para quem NAO esta autenticado (login, cadastro do portal).

    Blade puro de proposito, nao pagina Inertia: e a mesma politica que o
    App\Exceptions\Handler ja aplica no 404, de nao entregar o shell da aplicacao
    (branding, estrutura de navegacao, token CSRF) a visitante. Aqui nao ha
    nenhum link de navegacao nem nome de sistema - so o card, no mesmo visual de
    resources/js/Pages/Errors/NotFound.vue.

    Usuario autenticado nao chega aqui: o Handler intercepta o 429 e renderiza
    Errors/TooManyRequests dentro do layout.
--}}
@php
    $retryAfter = (int) ($exception?->getHeaders()['Retry-After'] ?? 0);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Muitas tentativas</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem 1rem;
            background: #0f172a;
            color: #e2e8f0;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .error-card {
            width: 100%;
            max-width: 520px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            backdrop-filter: blur(12px);
        }
        .error-code {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: rgba(148, 163, 184, 0.9);
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
        }
        .error-message {
            color: rgba(226, 232, 240, 0.85);
            margin: 0;
            line-height: 1.5;
        }
        .error-retry {
            margin: 1.25rem 0 0;
            font-size: 0.9375rem;
            color: rgba(148, 163, 184, 0.9);
        }
        .error-retry strong { color: #e2e8f0; font-variant-numeric: tabular-nums; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">429</div>
        <h1 class="error-title">Muitas tentativas</h1>
        <p class="error-message">
            Recebemos varias solicitacoes suas em pouco tempo e pausamos o acesso
            por seguranca. Aguarde um instante e tente novamente.
        </p>

        @if ($retryAfter > 0)
            <p class="error-retry" id="retry">
                Tente novamente em <strong id="retry-segundos">{{ $retryAfter }}</strong> segundos.
            </p>
            <script>
                (function () {
                    var alvo = document.getElementById('retry-segundos');
                    var restante = {{ $retryAfter }};

                    var timer = setInterval(function () {
                        restante -= 1;

                        if (restante > 0) {
                            alvo.textContent = restante;
                            return;
                        }

                        clearInterval(timer);
                        document.getElementById('retry').textContent = 'Voce ja pode tentar novamente.';
                    }, 1000);
                })();
            </script>
        @endif
    </div>
</body>
</html>
