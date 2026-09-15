/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

async function clearSwCachesAndReload() {
    try {
        if ('serviceWorker' in navigator) {
            const regs = await navigator.serviceWorker.getRegistrations();
            await Promise.all(regs.map(r => r.unregister()));
        }
        if ('caches' in window) {
            const keys = await caches.keys();
            await Promise.all(keys.map(k => caches.delete(k)));
        }
    } catch (_) {}
    window.location.reload();
}

export const initAxios = async () => {
    const axios = (await import('axios')).default;
    window.axios = axios;
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    window.axios.defaults.withCredentials = true;

    window.axios.interceptors.response.use(
        (response) => response,
        async (error) => {
            if (error.response?.status === 419) {
                await clearSwCachesAndReload();
                return;
            }

            if (error.response && error.response.status === 429) {
                const retryAfter = error.response.headers['retry-after'] || 60;

                // Dispara evento customizado para UI handling
                window.dispatchEvent(new CustomEvent('rate-limit-exceeded', {
                    detail: {
                        retryAfter: parseInt(retryAfter, 10),
                        message: error.response.data?.message || 'Limite de requisicoes excedido. Aguarde antes de tentar novamente.',
                    },
                }));
            }

            return Promise.reject(error);
        }
    );
};

/**
 * Echo (websocket via Laravel Reverb) para notificacoes em tempo real.
 *
 * Carregado por import() dinamico e SOB DEMANDA: quem nunca abre o painel de
 * notificacoes nunca baixa laravel-echo nem pusher-js. Isso mantem o bundle de
 * entrada intacto, que e o requisito de nao pesar a UI.
 *
 * Retorna a instancia de Echo, ou null quando o websocket nao esta configurado
 * ou falhou ao conectar. Null e um resultado esperado, nao um erro: o chamador
 * cai para polling.
 *
 * E o UNICO ponto que cria Echo na aplicacao. Um segundo ponto existiu (o
 * resources/js/echo.js, eager no app.js) e custou duas conexoes por aba, a
 * primeira orfa, mais laravel-echo e pusher-js dentro do chunk eager.
 *
 * As VITE_* sao lidas em BUILD TIME. Mudar host ou porta exige rebuild do
 * frontend, nao apenas restart de container -- e o tipo de coisa que vira uma
 * hora de diagnostico quando ninguem avisou.
 */
let echoPromise = null;

export const initEcho = () => {
    if (echoPromise) return echoPromise;

    const key = import.meta.env.VITE_REVERB_APP_KEY;

    // Sem chave publicada nao ha servidor de websocket para este ambiente.
    if (!key) return Promise.resolve(null);

    echoPromise = (async () => {
        try {
            const [{ default: Echo }, { default: Pusher }] = await Promise.all([
                import('laravel-echo'),
                import('pusher-js'),
            ]);

            window.Pusher = Pusher;

            const scheme = import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', '');
            const porta = import.meta.env.VITE_REVERB_PORT ?? (scheme === 'https' ? 443 : 8080);

            window.Echo = new Echo({
                broadcaster: 'reverb',
                key,
                // O NAVEGADOR alcanca o Reverb por localhost; o servidor, pelo
                // hostname de rede. Sao valores diferentes para a mesma coisa, e
                // por isso o host vem de uma VITE_ propria em vez de reaproveitar
                // REVERB_HOST.
                wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
                wsPort: Number(porta),
                wssPort: Number(porta),
                forceTLS: scheme === 'https',
                enabledTransports: ['ws', 'wss'],
                // O canal e privado: a autorizacao passa pela sessao ja existente.
                authEndpoint: '/broadcasting/auth',
            });

            return window.Echo;
        } catch (_) {
            // Reverb fora do ar, bloqueado por proxy ou chunk indisponivel:
            // devolver null para o chamador seguir de polling.
            echoPromise = null;
            return null;
        }
    })();

    return echoPromise;
};
