/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

export const initAxios = async () => {
    const axios = (await import('axios')).default;
    window.axios = axios;
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    window.axios.defaults.withCredentials = true;

    // Interceptor para Rate Limit (429)
    window.axios.interceptors.response.use(
        (response) => response,
        (error) => {
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
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
