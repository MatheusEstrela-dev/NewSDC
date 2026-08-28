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

        'LegadoRatIndex': [
            { label: 'Início', route: 'dashboard' },
            { label: 'RAT', route: 'rat.index' },
            { label: 'Arquivo Morto', route: null }
        ],
        'LegadoRatShow': [
            { label: 'Início', route: 'dashboard' },
            { label: 'RAT', route: 'rat.index' },
            { label: 'Arquivo Morto', route: 'rat.arquivados.index' },
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

        'Treinamento/TreinamentoIndex': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Treinamentos', route: null }
        ],
        'Treinamento/TreinamentoCreate': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Treinamentos', route: 'treinamentos.index' },
            { label: 'Novo Treinamento', route: null }
        ],
        'Treinamento/TreinamentoEdit': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Treinamentos', route: 'treinamentos.index' },
            { label: 'Edição', route: null }
        ],
        'Treinamento/TreinamentoShow': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Treinamentos', route: 'treinamentos.index' },
            { label: 'Visualizar', route: null }
        ],

        // Ajuda Humanitaria. Mapeado explicitamente porque o construtor
        // automatico deixa route: null em todo item intermediario, e o botao
        // Voltar percorre o breadcrumb procurando o primeiro item com rota:
        // sem estas entradas ele pulava a listagem e caia no Inicio. De quebra,
        // os rotulos ganham acento, que o humanize() da URL nao devolve.
        'AjudaHumanitaria/Dashboard': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: null }
        ],
        'AjudaHumanitaria/Pedidos/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Pedidos', route: null }
        ],
        'AjudaHumanitaria/Pedidos/Create': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Pedidos', route: 'ajuda-humanitaria.pedidos.index' },
            { label: 'Novo', route: null }
        ],
        'AjudaHumanitaria/Pedidos/Show': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Pedidos', route: 'ajuda-humanitaria.pedidos.index' },
            { label: 'Visualizar', route: null }
        ],
        'AjudaHumanitaria/Beneficiarios/BeneficiarioIndex': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Beneficiários', route: null }
        ],
        'AjudaHumanitaria/Beneficiarios/BeneficiarioShow': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Beneficiários', route: 'ajuda-humanitaria.beneficiarios.index' },
            { label: 'Visualizar', route: null }
        ],
        'AjudaHumanitaria/Estoque/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Estoque', route: null }
        ],
        'AjudaHumanitaria/Parametros/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Parâmetros', route: null }
        ],
        'AjudaHumanitaria/Movimentos/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Movimentações', route: null }
        ],
        'AjudaHumanitaria/Materiais/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Materiais', route: null }
        ],
        'AjudaHumanitaria/Entradas/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Entradas', route: null }
        ],
        'AjudaHumanitaria/Entradas/Show': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Entradas', route: 'ajuda-humanitaria.entradas.index' },
            { label: 'Visualizar', route: null }
        ],
        'AjudaHumanitaria/Liberacoes/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Liberações', route: null }
        ],
        'AjudaHumanitaria/Liberacoes/Show': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Liberações', route: 'ajuda-humanitaria.liberacoes.index' },
            { label: 'Visualizar', route: null }
        ],
        'AjudaHumanitaria/Transferencias/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Transferências', route: null }
        ],
        'AjudaHumanitaria/Transferencias/Show': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Ajuda Humanitária', route: 'ajuda-humanitaria.dashboard' },
            { label: 'Transferências', route: 'ajuda-humanitaria.transferencias.index' },
            { label: 'Visualizar', route: null }
        ],

        // Portal de Treinamentos (guard "cidadao") - mesmo shell do SDC, "Início"
        // aponta pro catalogo (nao existe dashboard interno pra ela).
        'Treinamento/Portal/Catalogo': [
            { label: 'Início', route: 'portal.treinamento.catalogo' },
            { label: 'Cursos e Eventos', route: null }
        ],
        'Treinamento/Portal/Detalhe': [
            { label: 'Início', route: 'portal.treinamento.catalogo' },
            { label: 'Cursos e Eventos', route: 'portal.treinamento.catalogo' },
            { label: 'Detalhes', route: null }
        ],
        'Treinamento/Portal/MinhasInscricoes': [
            { label: 'Início', route: 'portal.treinamento.catalogo' },
            { label: 'Minhas Inscrições', route: null }
        ],
        'Treinamento/Portal/Certificados': [
            { label: 'Início', route: 'portal.treinamento.catalogo' },
            { label: 'Certificados', route: null }
        ],

        // Admin / Permissions
        'Admin/Permissions/Users/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Usuários', route: null }
        ],
        'Admin/Permissions/Users/Show': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Usuários', route: 'admin.permissions.users.index' },
            { label: 'Visualizar', route: null }
        ],
        'Admin/Permissions/Users/Edit': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Usuários', route: 'admin.permissions.users.index' },
            { label: 'Edição', route: null }
        ],
        'Admin/Permissions/Users/Create': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Usuários', route: 'admin.permissions.users.index' },
            { label: 'Novo', route: null }
        ],
        'Admin/Permissions/Roles/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Cargos', route: null }
        ],
        'Admin/Permissions/Roles/Show': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Cargos', route: 'admin.permissions.roles.index' },
            { label: 'Visualizar', route: null }
        ],
        'Admin/Permissions/Roles/Edit': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Cargos', route: 'admin.permissions.roles.index' },
            { label: 'Edição', route: null }
        ],
        'Admin/Permissions/Roles/Create': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Cargos', route: 'admin.permissions.roles.index' },
            { label: 'Novo', route: null }
        ],
        'Admin/Permissions/Permissions/Index': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Permissões', route: null }
        ],
        'Admin/Permissions/Permissions/Show': [
            { label: 'Início', route: 'dashboard' },
            { label: 'Admin', route: null },
            { label: 'Permissões', route: 'admin.permissions.permissions.index' },
            { label: 'Visualizar', route: null }
        ],
        // ... (I will map other common ones or leave legacy strings to be handled by fallback)
    };

    const currentRoute = computed(() => page.component.value);

    /**
     * Trilha do modulo Cisterna.
     *
     * Fica fora do `breadcrumbMap` porque precisa das PROPS da pagina, e o mapa
     * e estatico: a trilha da vistoria volta para a lista de vistorias DAQUELE
     * beneficiario, e isso exige o id. Lida aqui dentro do computed, o valor
     * acompanha a navegacao.
     *
     * Sem estas entradas o fallback assumia: rotulava o modulo como "Cisterna"
     * no singular (vem do caminho do componente) e marcava `route: null` em
     * todos os niveis -- ou seja, "Cisterna" e "Beneficiarios" pareciam link e
     * nao levavam a lugar nenhum.
     */
    const trilhaCisterna = (componentName, props) => {
        if (!componentName.startsWith('Cisterna/')) {
            return null;
        }

        const inicio = { label: 'Início', route: 'dashboard' };
        const lista = { label: 'Cisternas', route: 'cisternas.beneficiarios.index' };

        const beneficiarioId = props?.beneficiario?.id ?? null;

        const doBeneficiario = beneficiarioId === null
            ? { label: 'Beneficiario', route: null }
            : {
                label: props.beneficiario.nome ?? 'Beneficiario',
                route: 'cisternas.beneficiarios.show',
                params: beneficiarioId,
            };

        const trilhas = {
            // A propria lista: ela e o fim da trilha, entao nao vira link.
            'Cisterna/Beneficiarios/Index': [inicio, { label: 'Cisternas', route: null }],
            'Cisterna/Beneficiarios/Create': [inicio, lista, { label: 'Novo cadastro', route: null }],
            // O nome, e nao "Visualizar": com os botoes de navegacao fora do
            // header, a trilha passou a ser o caminho, e "Visualizar" nao diz
            // QUAL cadastro esta aberto. Ultimo degrau nao vira link -- ele e a
            // propria pagina.
            'Cisterna/Beneficiarios/Show': [
                inicio,
                lista,
                { label: props?.beneficiario?.nome ?? 'Visualizar', route: null },
            ],
            'Cisterna/Beneficiarios/Edit': [inicio, lista, doBeneficiario, { label: 'Edição', route: null }],

            // Vistoria pertence a um beneficiario: a trilha passa por ele.
            'Cisterna/Vistorias/Index': [inicio, lista, doBeneficiario, { label: 'Vistorias', route: null }],
            'Cisterna/Vistorias/Show': [
                inicio,
                lista,
                doBeneficiario,
                beneficiarioId === null
                    ? { label: 'Vistorias', route: null }
                    : { label: 'Vistorias', route: 'cisternas.vistorias.index', params: beneficiarioId },
                { label: 'Relatorio', route: null },
            ],

            'Cisterna/Comunidades/Index': [inicio, lista, { label: 'Comunidades', route: null }],
            'Cisterna/Lotes/Index': [inicio, lista, { label: 'Lotes', route: null }],
            'Cisterna/OrdensServico/Index': [inicio, lista, { label: 'Ordens de servico', route: null }],
            'Cisterna/Notificacoes/Index': [inicio, lista, { label: 'Notificacoes', route: null }],
            'Cisterna/QrCode/Ficha': [inicio, lista, { label: 'Ficha do QR Code', route: null }],
        };

        return trilhas[componentName] ?? null;
    };

    const breadcrumbItems = computed(() => {
        const componentName = page.component?.value || page.component;

        if (!componentName) {
            return ['Início'];
        }

        const doCisterna = trilhaCisterna(componentName, page.props?.value ?? page.props);

        if (doCisterna) {
            return doCisterna;
        }

        if (breadcrumbMap[componentName]) {
            return breadcrumbMap[componentName];
        }

        const segments = componentName.split('/');
        const items = [{ label: 'Início', route: 'dashboard' }];

        const actionMap = {
            'Index': null,
            'Create': 'Novo',
            'Edit': 'Edição',
            'Show': 'Visualizar',
        };

        const humanize = (segment) => segment.replace(/([A-Z])/g, ' $1').trim();

        /**
         * Separa recurso e acao do ultimo segmento.
         *
         * O projeto usa DOIS estilos de nome de pagina, e o gerador so conhecia
         * um deles:
         *
         *   Tdap/Cronogramas/Index   acao como SEGMENTO proprio  (71 paginas)
         *   Plantao/ViaturasIndex    acao como SUFIXO do recurso (33 paginas)
         *
         * Sem tratar o sufixo, o segundo estilo caia no humanize() e a trilha
         * terminava em "Viaturas Index", "Escala Index", "Plantao Index" -- o
         * nome interno do arquivo vazando para o usuario final.
         */
        const separarAcao = (segmento) => {
            if (Object.prototype.hasOwnProperty.call(actionMap, segmento)) {
                return { recurso: null, acao: segmento };
            }

            for (const acao of Object.keys(actionMap)) {
                // `length >` e nao `>=`: o segmento tem que sobrar alguma coisa
                // depois de tirar a acao, senao "Index" viraria recurso vazio.
                if (segmento.length > acao.length && segmento.endsWith(acao)) {
                    return { recurso: segmento.slice(0, -acao.length), acao };
                }
            }

            return { recurso: segmento, acao: null };
        };

        const { recurso: recursoFinal, acao } = separarAcao(segments[segments.length - 1]);

        // Modulo (primeiro segmento): ex. Tdap
        if (segments[0]) {
            items.push({ label: humanize(segments[0]), route: null });
        }

        // Recursos intermediarios (entre modulo e acao): ex. Cronogramas
        for (let i = 1; i < segments.length - 1; i++) {
            items.push({ label: humanize(segments[i]), route: null });
        }

        // Recurso do ultimo segmento, quando sobra algo depois de tirar a acao
        if (recursoFinal && segments.length > 1) {
            items.push({ label: humanize(recursoFinal), route: null });
        }

        // Acao final (so quando mapeia para um rotulo nao nulo)
        if (acao && actionMap[acao]) {
            items.push({ label: actionMap[acao], route: null });
        }

        // Colapsa repeticao consecutiva: `Plantao/PlantaoIndex` produzia
        // "Plantao > Plantao", porque modulo e recurso tem o mesmo nome. E o
        // caso de toda pagina raiz de modulo neste projeto.
        return items.filter(
            (item, i) => i === 0 || item.label !== items[i - 1].label
        );
    });

    // Navega para o item anterior do proprio breadcrumb (deterministico) em vez
    // de depender do histórico do browser: window.history.back() as vezes exige
    // dois cliques (por exemplo apos um resize/re-render trocar a pilha de
    // historico), porque a pagina anterior real nem sempre e a entrada anterior
    // do historico.
    const handleBack = () => {
        const items = breadcrumbItems.value;

        for (let i = items.length - 2; i >= 0; i--) {
            if (items[i]?.route) {
                router.visit(route(items[i].route));
                return;
            }
        }

        window.history.back();
    };

    return {
        breadcrumbItems,
        currentRoute,
        handleBack
    };
}
