import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, h, type DefineComponent } from 'vue';
import { ZiggyVue } from 'ziggy-js';

/**
 * Inertia SSR Server
 *
 * Renderiza páginas Vue no servidor para:
 * - SEO otimizado (HTML pronto para crawlers)
 * - First Contentful Paint mais rápido (~50% melhora no LCP)
 * - Melhor experiência em conexões lentas
 */

const appName = import.meta.env.VITE_APP_NAME || 'SDC';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob<DefineComponent>('./Pages/**/*.vue', { eager: false })
            ),
        setup({ App, props, plugin }) {
            // QueryClient fresco por requisição — evita vazamento de estado entre requests SSR
            const queryClient = new QueryClient({
                defaultOptions: {
                    queries: {
                        staleTime: Infinity,
                        retry: 0,
                        enabled: false, // Dados vêm via props do Inertia, não via fetch durante SSR
                    },
                },
            });

            return createSSRApp({
                render: () => h(App, props),
            })
                .use(plugin)
                .use(ZiggyVue)
                .use(VueQueryPlugin, { queryClient });
        },
    })
);
