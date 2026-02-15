import createServer from '@inertiajs/server';
import { createInertiaApp } from '@inertiajs/vue3';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, h, type DefineComponent } from 'vue';
import { ZiggyVue } from 'ziggy-js';

/**
 * Inertia SSR Server
 *
 * Renderiza páginas Vue no servidor para:
 * - SEO otimizado (HTML pronto para crawlers)
 * - First Contentful Paint mais rápido
 * - Melhor experiência em conexões lentas
 */

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

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
            return createSSRApp({
                render: () => h(App, props),
            })
                .use(plugin)
                .use(ZiggyVue);
        },
    })
);
