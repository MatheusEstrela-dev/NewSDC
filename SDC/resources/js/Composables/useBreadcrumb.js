import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function useBreadcrumb() {
    const page = usePage();

    const breadcrumbMap = {
        'Dashboard': ['Início'],

        'Rat/RatIndex': ['Início', 'RAT'],
        'Rat/Create': ['Início', 'RAT', 'Novo RAT'],
        'Rat/Edit': ['Início', 'RAT', 'Edição'],
        'Rat/Show': ['Início', 'RAT', 'Visualizar'],

        'Pae/Pae': ['Início', 'PAE'],
        'Pae/Create': ['Início', 'PAE', 'Novo PAE'],
        'Pae/Edit': ['Início', 'PAE', 'Edição'],
        'Pae/Show': ['Início', 'PAE', 'Visualizar'],
        'Pae/PaeProtocolosIndex': ['Início', 'PAE', 'Protocolos'],
        'Pae/ProtocoloShow': ['Início', 'PAE', 'Protocolos', 'Visualizar'],

        'AjudaHumanitaria/Index': ['Início', 'Ajuda Humanitária'],
        'AjudaHumanitaria/Create': ['Início', 'Ajuda Humanitária', 'Nova Solicitação'],
        'AjudaHumanitaria/Edit': ['Início', 'Ajuda Humanitária', 'Edição'],
        'AjudaHumanitaria/Show': ['Início', 'Ajuda Humanitária', 'Visualizar'],

        'Demandas/Index': ['Início', 'Demandas'],
        'Demandas/Create': ['Início', 'Demandas', 'Nova Demanda'],
        'Demandas/Edit': ['Início', 'Demandas', 'Edição'],
        'Demandas/Show': ['Início', 'Demandas', 'Visualizar'],

        'Decretacoes/Index': ['Início', 'Decretações'],
        'Decretacoes/Create': ['Início', 'Decretações', 'Nova Decretação'],
        'Decretacoes/Edit': ['Início', 'Decretações', 'Edição'],
        'Decretacoes/Show': ['Início', 'Decretações', 'Visualizar'],

        'Compdec/Index': ['Início', 'Compdec'],
        'Compdec/Create': ['Início', 'Compdec', 'Novo Registro'],
        'Compdec/Edit': ['Início', 'Compdec', 'Edição'],
        'Compdec/Show': ['Início', 'Compdec', 'Visualizar'],

        'Tdap/Index': ['Início', 'TDAP'],
        'Tdap/Create': ['Início', 'TDAP', 'Novo Registro'],
        'Tdap/Edit': ['Início', 'TDAP', 'Edição'],
        'Tdap/Show': ['Início', 'TDAP', 'Visualizar'],

        'Treinamento/Index': ['Início', 'Treinamento'],
        'Treinamento/Create': ['Início', 'Treinamento', 'Novo Treinamento'],
        'Treinamento/Edit': ['Início', 'Treinamento', 'Edição'],
        'Treinamento/Show': ['Início', 'Treinamento', 'Visualizar'],

        'Admin/Index': ['Início', 'Administração'],
        'Admin/Users': ['Início', 'Administração', 'Usuários'],
        'Admin/Settings': ['Início', 'Administração', 'Configurações'],

        'LogViewer/Index': ['Início', 'Logs'],

        'Profile/Edit': ['Início', 'Perfil'],
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
        const items = ['Início'];

        if (segments[0]) {
            const moduleName = segments[0]
                .replace(/([A-Z])/g, ' $1')
                .trim();
            items.push(moduleName);
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
                items.push(action);
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
