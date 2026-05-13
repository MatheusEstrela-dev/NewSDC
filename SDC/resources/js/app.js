import '../css/app.css';
import { initAxios } from './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { startLoading, stopLoading } from '@/Composables/usePageLoading';

const isSamePagePath = (url) => {
    if (!url || typeof window === 'undefined') return false;
    try {
        const parsed = url instanceof URL ? url : new URL(url, window.location.origin);
        return parsed.pathname === window.location.pathname;
    } catch {
        return false;
    }
};

const isUserFacingNavigation = (visit) => {
    if (!visit || visit.method !== 'get') return false;
    if (visit.prefetch) return false;
    if (visit.only?.length > 0 || visit.except?.length > 0) return false;
    if (visit.async) return false;
    if (isSamePagePath(visit.url)) return false;
    return true;
};

router.on('start', (event) => {
    if (isUserFacingNavigation(event.detail?.visit)) {
        startLoading();
    }
});
router.on('finish', (event) => {
    if (isUserFacingNavigation(event.detail?.visit)) {
        stopLoading();
    }
});

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
            staleTime: 1000 * 60 * 5, // 5 minutos - evita polling excessivo
            gcTime: 1000 * 60 * 30,   // 30 minutos garbage collection
            refetchOnWindowFocus: false, // Desabilitado - evita burst de requisicoes
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
    let hoverTimer = null;

    const shouldPrefetch = () => {
        if (!navigator.connection) return true;
        const conn = navigator.connection;
        return conn.effectiveType === '4g' && !conn.saveData;
    };

    const doPrefetch = (href) => {
        if (!href || href.startsWith('#') || href.startsWith('http') || prefetchedRoutes.has(href)) {
            return;
        }
        if (!shouldPrefetch()) return;

        prefetchedRoutes.add(href);
        router.prefetch(href, { method: 'get' });
    };

    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;

        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => {
            doPrefetch(link.getAttribute('href'));
        }, 150);
    }, { passive: true });

    document.addEventListener('mouseout', (e) => {
        if (e.target.closest('a[href]')) {
            clearTimeout(hoverTimer);
        }
    }, { passive: true });

    document.addEventListener('touchstart', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        doPrefetch(link.getAttribute('href'));
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
                    if (!registration) return;

                    // Check for SW updates on Inertia page navigation
                    router.on('navigate', () => {
                        registration.update();
                    });

                    // Fallback: check every 2 hours
                    setInterval(() => {
                        registration.update();
                    }, 1000 * 60 * 120);
                },
                onOfflineReady() {
                },
            });
        } catch (e) {
            // Service Worker registration failed silently
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
            if (import.meta.env.PROD) {
                registerServiceWorker();
            }
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
        return '\u2B50';
    }
});
