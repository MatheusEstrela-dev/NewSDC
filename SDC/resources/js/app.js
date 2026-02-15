import '../css/app.css';
import { initAxios } from './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';

const loadPageCSS = (pageName) => {
    const cssMap = {
        'Dashboard': () => import('../css/pages/dashboard/dashboard.css'),
        'Pae': () => import('../css/pages/pae/pae.css'),
        'Rat': () => Promise.all([
            import('../css/pages/rat/rat.css'),
            import('../css/pages/rat/index.css'),
            import('../css/pages/rat/sections.css'),
        ]),
        'RatIndex': () => Promise.all([
            import('../css/pages/rat/rat.css'),
            import('../css/pages/rat/index.css'),
        ]),
        'Auth/Login': () => import('../css/pages/auth/login.css'),
        'Auth/Reset': () => import('../css/pages/auth/reset.css'),
    };

    // Suporte para nomes com subpasta (ex: 'Auth/Login' → tenta 'Auth/Login' e 'Login')
    const loader = cssMap[pageName] || cssMap[pageName.split('/').pop()];
    if (loader) {
        loader().catch(() => { });
    }
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 30, // 30s (SWR optimized)
            gcTime: 1000 * 60 * 30,
            refetchOnWindowFocus: true,
            refetchOnReconnect: true,
            retry: 1,
            retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
        },
        mutations: {
            retry: 0,
        },
    },
});

const prefetchedRoutes = new Set();

const setupPrefetching = () => {
    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('http') || prefetchedRoutes.has(href)) {
            return;
        }

        prefetchedRoutes.add(href);
        router.prefetch(href, { method: 'get' });
    }, { passive: true });

    document.addEventListener('touchstart', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('http') || prefetchedRoutes.has(href)) {
            return;
        }

        prefetchedRoutes.add(href);
        router.prefetch(href, { method: 'get' });
    }, { passive: true });
};

import { SyncService } from './infrastructure/services/SyncService';

const registerServiceWorker = async () => {
    if ('serviceWorker' in navigator) {
        try {
            const { registerSW } = await import('virtual:pwa-register');
            registerSW({
                immediate: true,
                onRegistered(registration) {
                    if (registration) {
                        setInterval(() => {
                            registration.update();
                        }, 1000 * 60 * 60);
                    }
                },
                onOfflineReady() {
                    console.log('App ready to work offline');
                },
            });
        } catch (e) {
            console.error('Service Worker registration failed:', e);
        }
    }
};

const appName = import.meta.env.VITE_APP_NAME || 'SDC';

await initAxios();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        loadPageCSS(name);
        return resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue', {
                eager: false,
                import: 'default'
            })
        );
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueQueryPlugin, { queryClient });

        app.mount(el);

        // Prefetching ativado imediatamente (leve, event listeners)
        setupPrefetching();

        // Service Worker e SyncService deferidos para não bloquear render inicial
        // requestIdleCallback executa quando o browser está ocioso (~1-2s após mount)
        const deferInit = window.requestIdleCallback || ((cb) => setTimeout(cb, 2000));
        deferInit(() => {
            registerServiceWorker();
            SyncService.init();
        });

        return app;
    },
    progress: {
        color: '#1e40af',
        showSpinner: true,
        delay: 25, // Evita flash em navegações rápidas (<150ms)
    },
});

Object.defineProperty(window, 'egg', {
    get: function () {
        console.log('%c\u2B50 DESENVOLVIDO POR MATHEUS ESTRELA \u2B50', 'color: #FFD700; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px #000;');
        return '\u2B50';
    }
});
