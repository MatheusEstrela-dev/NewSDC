import './bootstrap';
// CSS base - carregado imediatamente
import '../css/app.css';

// CSS lazy loading por página - carregado apenas quando necessário
// Nota: Login.css agora é importado diretamente no componente Login.vue
const loadPageCSS = (pageName) => {
    const cssMap = {
        // Login removido - importado diretamente no componente
        'Dashboard': () => import('../css/pages/dashboard/dashboard.css'),
        'Pae': () => import('../css/pages/pae/pae.css'),
    };
    
    const loader = cssMap[pageName];
    if (loader) {
        loader().catch(() => {
            // Ignorar erros de CSS não encontrado
        });
    }
};

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Carregar CSS específico da página em paralelo com o componente
        // Não aguardar CSS para não bloquear renderização
        loadPageCSS(name);
        
        // Resolver componente com lazy loading otimizado
        return resolvePageComponent(
            `./Pages/${name}.vue`, 
            import.meta.glob('./Pages/**/*.vue', { 
                eager: false, // Lazy loading explícito
                import: 'default' // Importar apenas default export
            })
        );
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
        showSpinner: false, // Remover spinner para carregamento mais rápido
        delay: 0, // Sem delay no progresso
    },
});
