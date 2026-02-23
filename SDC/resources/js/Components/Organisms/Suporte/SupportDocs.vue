<script setup>
import {
    AcademicCapIcon,
    ArrowTopRightOnSquareIcon,
    BookOpenIcon,
    BuildingOffice2Icon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClipboardDocumentCheckIcon,
    CloudIcon,
    DocumentIcon,
    DocumentTextIcon,
    FolderIcon,
    HeartIcon,
    InformationCircleIcon,
    LockClosedIcon,
    MagnifyingGlassIcon,
    ScaleIcon,
    Squares2X2Icon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const emit = defineEmits(['open-tickets', 'close']);

const activeModuleId = ref(null);
const searchTerm = ref('');
const mobileShowContent = ref(false);

const categories = [
    {
        title: "Principal",
        modules: [
            { id: 'visao-geral', label: 'Visão Geral', icon: Squares2X2Icon, desc: 'Painel de controle com indicadores estratégicos e resumo de atividades em tempo real.' },
            { id: 'rat', label: 'RAT', icon: DocumentTextIcon, desc: 'Relatório de Assistência Técnica. Utilizado para documentar vistorias, pareceres e atendimentos de campo.' },
            { id: 'demandas', label: 'Demandas', icon: ClipboardDocumentCheckIcon, desc: 'Gestão de tarefas e solicitações internas ou externas que requerem acompanhamento.' },
            { id: 'pae', label: 'PAE', icon: DocumentIcon, desc: 'Plano de Ação de Emergência. Estruturação de respostas para cenários de risco iminente.' },
        ]
    },
    {
        title: "Módulos de Gestão",
        modules: [
            { id: 'decretacoes', label: 'Decretações', icon: ScaleIcon, desc: 'Formalização de situações de emergência ou estado de calamidade pública.' },
            { id: 'ajuda', label: 'Ajuda Humanitária', icon: HeartIcon, desc: 'Gestão de donativos, logística de suprimentos e assistência a populações afetadas.' },
            { id: 'orgaos', label: 'Órgãos', icon: BuildingOffice2Icon, desc: 'Cadastro e integração de entidades parceiras e órgãos da administração pública.' },
            { id: 'tdap', label: 'TDAP', icon: FolderIcon, desc: 'Transferência de Dados de Apoio. Repositório de documentos técnicos e normativos.' },
            { id: 'treinamento', label: 'Treinamento', icon: AcademicCapIcon, desc: 'Gestão de cursos, capacitações e simulados para as equipas de defesa civil.' },
            { id: 'meteorologia', label: 'Meteorologia', icon: CloudIcon, desc: 'Monitoramento de condições climáticas, alertas de chuva e riscos geo-hidrológicos.' },
            { id: 'vistoria', label: 'Vistoria', icon: ClipboardDocumentCheckIcon, desc: 'Módulo específico para inspeções técnicas de engenharia e avaliação de danos.' },
        ]
    },
    {
        title: "Administração",
        modules: [
            { id: 'permissao', label: 'Permissionamento', icon: LockClosedIcon, desc: 'Controle de perfis de acesso, hierarquias e segurança de dados do sistema.' },
        ]
    }
];

// Flatten modules for easy access
const allModules = categories.flatMap(c => c.modules);

const currentModule = computed(() => {
    return allModules.find(m => m.id === activeModuleId.value) || allModules[1]; // default RAT
});

const filteredCategories = computed(() => {
    if (!searchTerm.value) return categories;

    return categories.map(cat => ({
        ...cat,
        modules: cat.modules.filter(m =>
            m.label.toLowerCase().includes(searchTerm.value.toLowerCase())
        )
    })).filter(cat => cat.modules.length > 0);
});

const faqs = [
    { q: "Quem pode acessar?", a: "Disponível para perfis de nível Gestor e Coordenador Estadual." },
    { q: "Quais dados são gerados?", a: "Relatórios analíticos exportáveis em PDF e tabelas CSV." },
    { q: "Integração", a: "Este módulo comunica-se diretamente com o Permissionamento e RAT." },
    { q: "Frequência de Uso", a: "Uso diário para monitoramento e resposta a incidentes." }
];

const selectModule = (modId) => {
    activeModuleId.value = modId;
    mobileShowContent.value = true;
};

const mobileBack = () => {
    mobileShowContent.value = false;
};

// Initialize with RAT selected
activeModuleId.value = 'rat';
</script>

<template>
    <div class="flex h-full bg-white dark:bg-slate-900 overflow-hidden">
        <!-- Sidebar (desktop always visible, mobile: hidden when viewing content) -->
        <aside
            class="flex flex-col bg-slate-50 dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700"
            :class="mobileShowContent ? 'hidden md:flex md:w-80' : 'w-full md:w-80'"
        >
            <div class="p-4 sm:p-6 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 sticky top-0 z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-600 rounded-xl shadow-lg shadow-blue-200 dark:shadow-blue-900/20">
                            <BookOpenIcon class="text-white w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white leading-tight">Guia do Sistema</h2>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Manual de Módulos</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="md:hidden p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-slate-400">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="relative group">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-600 transition-colors w-4 h-4" />
                    <input
                        type="text"
                        placeholder="Buscar módulo..."
                        v-model="searchTerm"
                        class="w-full pl-10 pr-4 py-3 sm:py-2 bg-slate-100 dark:bg-slate-700 border-none rounded-xl text-base sm:text-sm focus:ring-2 focus:ring-blue-500 dark:text-white outline-none transition-all placeholder-slate-400"
                    />
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-4 sm:space-y-6 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600">
                <div v-for="(cat, idx) in filteredCategories" :key="idx" class="space-y-1.5 sm:space-y-2">
                    <h3 class="px-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.1em]">{{ cat.title }}</h3>
                    <div class="space-y-1">
                        <button
                            v-for="mod in cat.modules"
                            :key="mod.id"
                            @click="selectModule(mod.id)"
                            class="w-full flex items-center justify-between px-4 py-3 sm:py-2.5 rounded-xl transition-all duration-200 text-sm font-medium group"
                            :class="activeModuleId === mod.id
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-100 dark:shadow-none'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white hover:shadow-sm border border-transparent hover:border-slate-200 dark:hover:border-slate-600'"
                        >
                            <div class="flex items-center gap-3">
                                <component :is="mod.icon" class="w-5 h-5 sm:w-[18px] sm:h-[18px]" />
                                {{ mod.label }}
                            </div>
                            <ChevronRightIcon class="w-3.5 h-3.5" :class="activeModuleId === mod.id ? 'opacity-100' : 'opacity-0 md:opacity-0 group-hover:opacity-50'" />
                        </button>
                    </div>
                </div>
            </nav>

            <div class="p-3 sm:p-4 bg-slate-100/50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <button
                    @click="$emit('open-tickets')"
                    class="w-full flex items-center justify-center gap-2 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all"
                >
                    <InformationCircleIcon class="w-3.5 h-3.5" />
                    Central de Chamados
                </button>
            </div>
        </aside>

        <!-- Main Content (desktop always visible, mobile: shown when module selected) -->
        <main
            class="flex-1 flex flex-col bg-white dark:bg-slate-900 overflow-hidden relative"
            :class="mobileShowContent ? 'flex' : 'hidden md:flex'"
        >
            <!-- Mobile back button (sticky top) -->
            <div class="flex md:hidden items-center gap-2 px-4 py-3 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-20">
                <button @click="mobileBack" class="flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 p-1 -ml-1">
                    <ChevronLeftIcon class="w-5 h-5" />
                    Voltar
                </button>
                <span class="text-slate-300 dark:text-slate-600">|</span>
                <span class="text-sm font-bold text-slate-700 dark:text-white truncate flex-1">{{ currentModule.label }}</span>
                <button @click="$emit('close')" class="p-1 text-slate-400">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <header class="p-4 sm:p-8 flex justify-between items-start border-b border-slate-50 dark:border-slate-800">
                <div class="flex items-center gap-3 sm:gap-5">
                    <div class="p-2.5 sm:p-4 bg-slate-50 dark:bg-slate-800 rounded-xl sm:rounded-2xl border border-slate-100 dark:border-slate-700">
                        <component :is="currentModule.icon" class="w-6 h-6 sm:w-10 sm:h-10 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-bold rounded uppercase tracking-wider">Módulo Ativo</span>
                            <h1 class="text-xl sm:text-3xl font-black text-slate-800 dark:text-white tracking-tight">{{ currentModule.label }}</h1>
                        </div>
                        <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1 font-medium italic">Entenda como utilizar esta ferramenta no SDC MG.</p>
                    </div>
                </div>
                <button @click="$emit('close')" class="hidden md:block p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full text-slate-300 hover:text-slate-500 dark:hover:text-slate-200 transition-colors">
                    <XMarkIcon class="w-6 h-6" />
                </button>
            </header>

            <div class="flex-1 overflow-y-auto p-4 sm:p-8 lg:p-12 space-y-6 sm:space-y-10 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600">
                <!-- Description -->
                <section>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 sm:mb-4 flex items-center gap-2">
                        <div class="w-4 h-[2px] bg-blue-600"></div>
                        O que é este módulo?
                    </h4>
                    <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                        {{ currentModule.desc }}
                    </p>
                </section>

                <!-- FAQ Grid -->
                <section>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 sm:mb-6">Principais Funcionalidades</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-6">
                        <div v-for="(item, i) in faqs" :key="i" class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800 rounded-xl sm:rounded-2xl border border-slate-100 dark:border-slate-700 hover:border-blue-200 dark:hover:border-blue-700 hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all cursor-default group">
                            <h5 class="font-bold text-slate-800 dark:text-white mb-1.5 sm:mb-2 flex items-center gap-2 text-sm sm:text-base">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></div>
                                {{ item.q }}
                            </h5>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-snug group-hover:text-slate-600 dark:group-hover:text-slate-300">{{ item.a }}</p>
                        </div>
                    </div>
                </section>

                <!-- Expert Tip -->
                <div class="p-5 sm:p-8 bg-indigo-600 dark:bg-indigo-700 rounded-2xl sm:rounded-3xl text-white flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 shadow-xl shadow-indigo-100 dark:shadow-none">
                    <div class="p-3 sm:p-4 bg-white/10 rounded-xl sm:rounded-2xl backdrop-blur-md">
                        <InformationCircleIcon class="w-6 h-6 sm:w-8 sm:h-8" />
                    </div>
                    <div>
                        <h4 class="font-bold text-base sm:text-lg">Dica de Utilização</h4>
                        <p class="text-indigo-100 text-xs sm:text-sm mt-1 leading-relaxed">
                            Para obter melhores resultados no módulo de <b>{{ currentModule.label }}</b>, certifique-se de que os dados geográficos estão atualizados nas Configurações de Regionalização.
                        </p>
                    </div>
                </div>
            </div>

            <footer class="p-4 sm:p-8 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white dark:bg-slate-900 sticky bottom-0 z-20 md:static">
                <button class="hidden sm:flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    <BookOpenIcon class="w-4 h-4" /> Ver Documentação Completa
                </button>
                <button class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-3.5 sm:py-3 bg-blue-600 sm:bg-slate-800 dark:bg-blue-600 dark:sm:bg-slate-700 text-white rounded-xl text-base sm:text-sm font-bold hover:bg-blue-700 sm:hover:bg-slate-900 dark:hover:bg-blue-500 dark:sm:hover:bg-slate-600 transition-all active:scale-95 shadow-lg sm:shadow-none shadow-blue-200 dark:shadow-blue-900/30">
                    Abrir Módulo de {{ currentModule.label }}
                    <ArrowTopRightOnSquareIcon class="w-5 h-5 sm:w-4 sm:h-4" />
                </button>
            </footer>
        </main>
    </div>
</template>
