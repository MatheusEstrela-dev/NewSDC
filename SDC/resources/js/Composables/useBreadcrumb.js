import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function useBreadcrumb() {
    const page = usePage();

    const breadcrumbMap = {
        'Dashboard': [], // Dashboard is home

        'Rat/RatIndex': [
            { label: 'Início', route: 'dashboard' },
            { label: 'RAT', route: null }
        ],
        'Rat/Create': [
            { label: 'Início', route: 'dashboard' },
            { label: 'RAT', route: 'rat.index' },
            { label: 'Novo RAT', route: null }
        ],
        'Rat/Edit': [
            { label: 'Início', route: 'dashboard' },
            { label: 'RAT', route: 'rat.index' },
            { label: 'Edição', route: null }
        ],
        'Rat/Show': [
            { label: 'Início', route: 'dashboard' },
            { label: 'RAT', route: 'rat.index' },
            { label: 'Visualizar', route: null }
        ],

        'Pae/Pae': [
            { label: 'Início', route: 'dashboard' },
            { label: 'PAE', route: null }
        ],
        'Pae/Create': [
            { label: 'Início', route: 'dashboard' },
            { label: 'PAE', route: 'pae.index' },
            { label: 'Novo PAE', route: null }
        ],
        // ... (I will map other common ones or leave legacy strings to be handled by fallback)
    };

    const currentRoute = computed(() => page.component.value);

    const breadcrumbItems = computed(() => {
        const componentName = page.component?.value || page.component;

        if (!componentName) {
            return ['Início'];
        }

        if (breadcrumbMap[componentName]) {
            return breadcrumbMap[componentName];
        }

        const segments = componentName.split('/');
        const items = [{ label: 'Início', route: 'dashboard' }];

        if (segments[0]) {
            const moduleName = segments[0]
                .replace(/([A-Z])/g, ' $1')
                .trim();
            // TODO: Try to infer route? e.g. lower(moduleName) + '.index'
            items.push({ label: moduleName, route: null });
        }

        if (segments[1]) {
            const actionMap = {
                'Index': null,
                'Create': 'Novo',
                'Edit': 'Edição',
                'Show': 'Visualizar',
            };

            const action = actionMap[segments[1]];
            if (action) {
                items.push({ label: action, route: null });
            }
        }

        return items;
    });

    const handleBack = () => {
        window.history.back();
    };

    return {
        breadcrumbItems,
        currentRoute,
        handleBack
    };
}
