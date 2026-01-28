<script setup>
import { ref, computed } from 'vue';
import { 
    Squares2X2Icon, 
    DocumentTextIcon, 
    ClipboardDocumentCheckIcon, 
    DocumentIcon, 
    ScaleIcon, 
    HeartIcon, 
    BuildingOffice2Icon, 
    FolderIcon, 
    AcademicCapIcon, 
    CloudIcon, 
    LockClosedIcon, 
    MagnifyingGlassIcon, 
    ChevronRightIcon, 
    InformationCircleIcon, 
    ArrowTopRightOnSquareIcon, 
    BookOpenIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const emit = defineEmits(['open-tickets', 'close']);

const activeModuleId = ref('rat');
const searchTerm = ref('');

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
    return allModules.find(m => m.id === activeModuleId.value) || allModules[0];
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

</script>

<template>
    <div class="flex h-full bg-white dark:bg-slate-900 overflow-hidden rounded-xl">
        <!-- Sidebar -->
        <aside class="w-80 bg-slate-50 dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex flex-col">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-blue-600 rounded-xl shadow-lg shadow-blue-200 dark:shadow-blue-900/20">
                        <BookOpenIcon class="text-white w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-white leading-tight">Guia do Sistema</h2>
                        <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Manual de Módulos</p>
                    </div>
                </div>

                <div class="relative group">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-600 transition-colors w-4 h-4" />
                    <input 
                        type="text" 
                        placeholder="Buscar módulo..."
                        v-model="searchTerm"
                        class="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-700 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white outline-none transition-all placeholder-slate-400"
                    />
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-6 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600">
                <div v-for="(cat, idx) in filteredCategories" :key="idx" class="space-y-2">
                    <h3 class="px-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.1em]">{{ cat.title }}</h3>
                    <div class="space-y-1">
                        <button
                            v-for="mod in cat.modules"
                            :key="mod.id"
                            @click="activeModuleId = mod.id"
                            class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all duration-200 text-sm font-medium group"
                            :class="activeModuleId === mod.id 
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-100 dark:shadow-none' 
                                : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white hover:shadow-sm border border-transparent hover:border-slate-200 dark:hover:border-slate-600'"
                        >
                            <div class="flex items-center gap-3">
                                <component :is="mod.icon" class="w-[18px] h-[18px]" />
                                {{ mod.label }}
                            </div>
                            <ChevronRightIcon v-if="activeModuleId === mod.id" class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </nav>

            <div class="p-4 bg-slate-100/50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <button 
                    @click="$emit('open-tickets')"
                    class="w-full flex items-center justify-center gap-2 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all"
                >
                    <InformationCircleIcon class="w-3.5 h-3.5" />
                    Central de Chamados
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col bg-white dark:bg-slate-900 overflow-hidden relative">
            <header class="p-8 flex justify-between items-start border-b border-slate-50 dark:border-slate-800">
                <div class="flex items-center gap-5">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
                        <component :is="currentModule.icon" class="w-10 h-10 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-bold rounded uppercase tracking-wider">Módulo Ativo</span>
                            <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight">{{ currentModule.label }}</h1>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium italic">Entenda como utilizar esta ferramenta no SDC MG.</p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full text-slate-300 hover:text-slate-500 dark:hover:text-slate-200 transition-colors">
                    <XMarkIcon class="w-6 h-6" />
                </button>
            </header>

            <div class="flex-1 overflow-y-auto p-12 space-y-10 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600">
                <!-- Description -->
                <section>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <div class="w-4 h-[2px] bg-blue-600"></div>
                        O que é este módulo?
                    </h4>
                    <p class="text-lg text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                        {{ currentModule.desc }}
                    </p>
                </section>

                <!-- FAQ Grid -->
                <section>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Principais Funcionalidades</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="(item, i) in faqs" :key="i" class="p-6 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 hover:border-blue-200 dark:hover:border-blue-700 hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all cursor-default group">
                            <h5 class="font-bold text-slate-800 dark:text-white mb-2 flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                                {{ item.q }}
                            </h5>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-snug group-hover:text-slate-600 dark:group-hover:text-slate-300">{{ item.a }}</p>
                        </div>
                    </div>
                </section>

                <!-- Expert Tip -->
                <div class="p-8 bg-indigo-600 dark:bg-indigo-700 rounded-3xl text-white flex flex-col md:flex-row items-start md:items-center gap-6 shadow-xl shadow-indigo-100 dark:shadow-none">
                    <div class="p-4 bg-white/10 rounded-2xl backdrop-blur-md">
                        <InformationCircleIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <h4 class="font-bold text-lg">Dica de Utilização</h4>
                        <p class="text-indigo-100 text-sm mt-1 leading-relaxed">
                            Para obter melhores resultados no módulo de <b>{{ currentModule.label }}</b>, certifique-se de que os dados geográficos estão atualizados nas Configurações de Regionalização.
                        </p>
                    </div>
                </div>
            </div>

            <footer class="p-8 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900">
                <div class="flex gap-4">
                    <button class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <BookOpenIcon class="w-4 h-4" /> Ver Documentação Completa
                    </button>
                </div>
                <button class="flex items-center gap-2 px-6 py-3 bg-slate-800 dark:bg-slate-700 text-white rounded-xl text-sm font-bold hover:bg-slate-900 dark:hover:bg-slate-600 transition-all active:scale-95">
                    Abrir Módulo de {{ currentModule.label }}
                    <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                </button>
            </footer>
        </main>
    </div>
</template>
