import './bootstrap';
import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query';

const loadPageCSS = (pageName) => {
    const cssMap = {
        'Dashboard': () => import('../css/pages/dashboard/dashboard.css'),
        'Pae': () => import('../css/pages/pae/pae.css'),
    };

    const loader = cssMap[pageName];
    if (loader) {
        loader().catch(() => { });
    }
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 5,
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

        setupPrefetching();
        registerServiceWorker();
        SyncService.init(); // Inicializa o serviço de sincronização

        return app;
    },
    progress: {
        color: '#1e40af',
        showSpinner: false,
        delay: 0,
    },
});
