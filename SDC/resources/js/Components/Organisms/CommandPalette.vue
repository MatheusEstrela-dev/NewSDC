<template>
  <div class="contents">
    <!-- Modal Backdrop -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] bg-slate-900/50 backdrop-blur-sm"
        @click="close"
      ></div>
    </Transition>

    <!-- Command Palette Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 translate-y-4"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-4"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[101] flex items-start justify-center px-2 sm:px-4 pb-4 pt-[max(env(safe-area-inset-top,0px),2.5rem)] sm:pt-12 sm:px-12 md:p-24 pointer-events-none"
      >
        <div
          class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden pointer-events-auto flex flex-col max-h-[75vh] sm:max-h-[70vh]"
          @keydown.esc="close"
          @keydown.down.prevent="navigateResults(1)"
          @keydown.up.prevent="navigateResults(-1)"
          @keydown.enter.prevent="selectResult"
        >
          <!-- Search Input -->
          <div class="relative border-b border-slate-200 dark:border-slate-700">
            <svg
              class="absolute left-4 top-3.5 w-5 h-5 text-slate-400 dark:text-slate-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
            <input
              ref="searchInput"
              type="text"
              v-model="query"
              @input="handleInput"
              class="w-full pl-12 pr-4 py-3.5 text-base bg-transparent border-none outline-none text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500"
              placeholder="Busque por módulos, ações ou configurações..."
              autocomplete="off"
            />
            <div class="absolute right-3 top-2.5 flex items-center gap-1.5">
               <button
                 @click="close"
                 class="sm:hidden flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 active:bg-slate-200 dark:active:bg-slate-700 transition-colors"
                 aria-label="Fechar"
               >
                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
               </button>
               <span class="hidden sm:inline text-xs font-medium text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">ESC</span>
            </div>
          </div>

          <!-- Results Area -->
          <div class="flex-1 overflow-y-auto min-h-[100px] scrollbar-thin">
            <!-- Loading -->
            <div
              v-if="isLoading"
              class="flex flex-col items-center justify-center py-12 text-slate-500 dark:text-slate-400"
            >
              <svg class="animate-spin h-6 w-6 mb-3 text-blue-500" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-sm">Buscando em todo o sistema...</span>
            </div>

            <!-- Empty State -->
            <div
              v-else-if="!hasResults && query.length >= 3"
              class="flex flex-col items-center justify-center py-12 text-slate-500 dark:text-slate-400"
            >
              <svg class="w-10 h-10 mb-3 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-sm">Não encontramos nada para "<span class="font-medium text-slate-900 dark:text-slate-100">{{ query }}</span>"</p>
            </div>

             <!-- Initial State (Quick Actions / Recent) -->
             <div v-else-if="query.length < 3" class="p-2">
                 <div class="px-3 py-2 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                    Sugestões Rápidas
                 </div>
                 <button
                    v-for="(action, index) in quickActions"
                    :key="action.id"
                    @click="executeAction(action)"
                    class="w-full text-left px-3 py-2.5 rounded-lg flex items-center gap-3 transition-colors group"
                    :class="{'bg-blue-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400': activeIndex === index, 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800': activeIndex !== index}"
                    @mouseenter="activeIndex = index"
                 >
                     <div class="flex-shrink-0 w-8 h-8 rounded-md flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 group-hover:bg-white dark:group-hover:bg-slate-700 group-hover:text-blue-500 dark:group-hover:text-blue-400 shadow-sm transition-colors">
                        <component :is="getIconComponent(action.icon)" class="w-4 h-4" />
                     </div>
                     <span class="flex-1 text-sm font-medium">{{ action.title }}</span>
                     <span class="text-xs text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 opacity-0 group-hover:opacity-100 transition-opacity">Jump to</span>
                 </button>
             </div>

            <!-- Search Results -->
            <div v-else class="py-2">
              <template v-for="(categoryItems, categoryName) in results" :key="categoryName">
                <div v-if="categoryItems.length > 0">
                    <div class="sticky top-0 z-10 bg-white/95 dark:bg-slate-900/95 backdrop-blur px-4 py-2 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 mb-1">
                        {{ getCategoryLabel(categoryName) }}
                    </div>
                    <div class="px-2">
                        <button
                            v-for="(item, index) in categoryItems"
                            :key="item.uniqueId"
                            @click="selectResult(item)"
                            class="w-full text-left px-3 py-2.5 mb-1 rounded-lg flex items-start gap-3 transition-colors group relative"
                            :class="{
                                'bg-blue-600 text-white shadow-md': isActive(item),
                                'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800': !isActive(item)
                            }"
                            @mouseenter="setActive(item)"
                        >
                            <div
                                class="flex-shrink-0 w-8 h-8 rounded-md flex items-center justify-center shadow-sm transition-colors"
                                :class="{
                                    'bg-white/20 text-white': isActive(item),
                                    'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 group-hover:bg-white dark:group-hover:bg-slate-700': !isActive(item)
                                }"
                            >
                                <component :is="getIconComponent(item.icon)" class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold truncate" :class="isActive(item) ? 'text-white' : 'text-slate-900 dark:text-slate-100'">
                                    {{ item.title }}
                                </div>
                                <div class="text-xs truncate flex items-center gap-2" :class="isActive(item) ? 'text-blue-100' : 'text-slate-500 dark:text-slate-400'">
                                    <span>{{ item.subtitle }}</span>
                                    <span v-if="item.tag" class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-black/10 text-current">
                                        {{ item.tag }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="isActive(item)" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </div>
                        </button>
                    </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="px-4 py-2.5 sm:py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <!-- Mobile: simplified footer -->
            <div class="sm:hidden flex items-center gap-2">
              <span class="text-slate-400 dark:text-slate-500">Toque para selecionar</span>
            </div>
            <!-- Desktop: keyboard shortcuts -->
            <div class="hidden sm:flex items-center gap-4">
              <span class="flex items-center gap-1">
                <kbd class="font-sans px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-md text-[10px]">↩</kbd>
                Selecionar
              </span>
              <span class="flex items-center gap-1">
                <kbd class="font-sans px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-md text-[10px]">↑</kbd>
                <kbd class="font-sans px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-md text-[10px]">↓</kbd>
                Navegar
              </span>
              <span class="flex items-center gap-1">
                <kbd class="font-sans px-1.5 py-0.5 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-md text-[10px]">ESC</kbd>
                Fechar
              </span>
            </div>
            <div>
              <span class="text-slate-400 dark:text-slate-600 text-[10px] sm:text-xs">Intelligent Search</span>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import {
  AcademicCapIcon,
  ArrowRightOnRectangleIcon,
  BuildingOfficeIcon,
  ClipboardDocumentCheckIcon,
  CloudIcon,
  Cog6ToothIcon,
  CommandLineIcon,
  DocumentTextIcon,
  HeartIcon,
  HomeIcon,
  ScaleIcon,
  ShieldCheckIcon,
  UserIcon
} from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close']);

const query = ref('');
const searchInput = ref(null);
const results = ref({});
const isLoading = ref(false);
const activeIndex = ref(0);
let searchTimeout = null;

// Mapa de ícones
const iconMap = {
    document: DocumentTextIcon,
    checkbadge: ClipboardDocumentCheckIcon,
    user: UserIcon,
    dashboard: HomeIcon,
    scale: ScaleIcon,
    heart: HeartIcon,
    building: BuildingOfficeIcon,
    academic: AcademicCapIcon,
    cloud: CloudIcon,
    shield: ShieldCheckIcon,
    settings: Cog6ToothIcon,
    logout: ArrowRightOnRectangleIcon,
    default: CommandLineIcon
};

const getIconComponent = (name) => {
    return iconMap[name] || iconMap.default;
};

// Safe Route Helper
const safeRoute = (name, fallback = '') => {
    return route().has(name) ? route(name) : fallback;
};

// Static Navigation Data (Indexed for client-side search)
const navigationIndex = computed(() => [
    // Principal
    { id: 'nav_dash', title: 'Visão Geral', subtitle: 'Dashboard Principal', url: safeRoute('dashboard'), icon: 'dashboard', category: 'navigation', keywords: ['home', 'inicio', 'painel'] },
    { id: 'nav_rat', title: 'RAT', subtitle: 'Relatório de Atendimento Técnico', url: safeRoute('rat.index'), icon: 'document', category: 'navigation', keywords: ['vistoria', 'relatorio', 'tecnico'] },
    { id: 'nav_dem', title: 'Demandas', subtitle: 'Gestão de Chamados e Tarefas', url: safeRoute('demandas.index'), icon: 'checkbadge', category: 'navigation', keywords: ['chamado', 'ticket', 'tarefa'] },
    { id: 'nav_pae', title: 'PAE', subtitle: 'Plano de Ação de Emergência', url: safeRoute('pae.index', safeRoute('pae.protocolos.index')), icon: 'document', category: 'navigation', keywords: ['plano', 'emergencia', 'protocolo'] },
    
    // Módulos de Gestão
    { id: 'nav_dec', title: 'Decretações', subtitle: 'Gestão de Decretos (SEAD)', url: safeRoute('decretacoes.index'), icon: 'scale', category: 'navigation', keywords: ['decreto', 'legal', 'calamidade'] },
    { id: 'nav_hum', title: 'Ajuda Humanitária', subtitle: 'Beneficiários e Entregas', url: safeRoute('ajuda-humanitaria.beneficiarios.index'), icon: 'heart', category: 'navigation', keywords: ['donativo', 'cesta', 'social'] },
    { id: 'nav_org', title: 'Órgãos', subtitle: 'Cadastro de COMPDEC e Parceiros', url: safeRoute('compdec.index'), icon: 'building', category: 'navigation', keywords: ['municipio', 'prefeitura', 'contato'] },
    
    // TDAP
    { id: 'nav_tdap_dash', title: 'TDAP Dashboard', subtitle: 'Visão Geral TDAP', url: safeRoute('tdap.dashboard'), icon: 'document', category: 'navigation', keywords: ['tdap', 'cartao', 'pagamento'] },
    { id: 'nav_tdap_prod', title: 'TDAP Produtos', subtitle: 'Gestão de Produtos', url: safeRoute('tdap.products.index'), icon: 'document', category: 'navigation', keywords: ['tdap', 'item', 'estoque'] },
    
    // Outros
    { id: 'nav_treino', title: 'Treinamentos', subtitle: 'Cursos e Capacitações', url: safeRoute('treinamentos.index'), icon: 'academic', category: 'navigation', keywords: ['curso', 'aula', 'ead'] },
    { id: 'nav_meteo', title: 'Meteorologia', subtitle: 'Alertas e Previsão (INMET)', url: safeRoute('inmet.index'), icon: 'cloud', category: 'navigation', keywords: ['clima', 'tempo', 'chuva'] },
    
    // Admin
    { id: 'adm_users', title: 'Usuários', subtitle: 'Gestão de Acesso', url: safeRoute('admin.permissions.users.index'), icon: 'shield', category: 'admin', keywords: ['admin', 'permissoes', 'login'] },
    { id: 'adm_roles', title: 'Cargos e Funções', subtitle: 'Definição de Papéis', url: safeRoute('admin.permissions.roles.index'), icon: 'shield', category: 'admin', keywords: ['admin', 'roles', 'cargos'] },

    // Actions
    { id: 'act_rat', title: 'Novo RAT', subtitle: 'Criar Relatório', url: safeRoute('rat.create'), icon: 'document', category: 'actions', keywords: ['criar', 'novo', 'adicionar'] },
    { id: 'act_dem', title: 'Nova Demanda', subtitle: 'Abrir Chamado', url: safeRoute('demandas.create'), icon: 'checkbadge', category: 'actions', keywords: ['criar', 'novo', 'adicionar'] },
    { id: 'act_logout', title: 'Sair do Sistema', subtitle: 'Fazer Logout', url: safeRoute('logout'), icon: 'logout', category: 'actions', method: 'post', keywords: ['sair', 'logoff'] },
]);

// Quick Actions — acoes e navegacao rapida
const quickActions = computed(() => [
  // Acoes rapidas
  { id: 'qa_rat',  title: 'Novo RAT',      subtitle: 'Criar Relatório de Atendimento', url: safeRoute('rat.create'),      icon: 'document',    type: 'action' },
  { id: 'qa_dem',  title: 'Nova Demanda',   subtitle: 'Abrir um chamado',              url: safeRoute('demandas.create'),  icon: 'checkbadge',  type: 'action' },
  // Modulos principais
  { id: 'qa_dec',  title: 'Decretações',    subtitle: 'Gestão de Decretos (SEAD)',     url: safeRoute('decretacoes.index'), icon: 'scale',       type: 'navigation' },
  { id: 'qa_pae',  title: 'PAE',            subtitle: 'Plano de Ação de Emergência',   url: safeRoute('pae.index', safeRoute('pae.protocolos.index')), icon: 'document', type: 'navigation' },
  { id: 'qa_hum',  title: 'Ajuda Humanitária', subtitle: 'Beneficiários e Entregas',  url: safeRoute('ajuda-humanitaria.beneficiarios.index'), icon: 'heart', type: 'navigation' },
  { id: 'qa_org',  title: 'Órgãos',         subtitle: 'COMPDEC e Parceiros',          url: safeRoute('compdec.index'),    icon: 'building',    type: 'navigation' },
  // Conta
  { id: 'qa_prof', title: 'Meu Perfil',     subtitle: 'Configurações da conta',       url: '/profile?open_profile=true',  icon: 'user',        type: 'action' },
]);

const flattenedResults = computed(() => {
    // If empty query, return quick actions
    if (query.value.length < 3) return quickActions.value;

    let flat = [];
    const priorityOrder = ['actions', 'navigation', 'admin', 'db_results'];
    
    priorityOrder.forEach(cat => {
        if (results.value[cat]) {
            flat = [...flat, ...results.value[cat]];
        }
    });
    return flat;
});

const hasResults = computed(() => flattenedResults.value.length > 0);

watch(() => props.isOpen, (val) => {
  if (val) {
    query.value = '';
    results.value = {};
    activeIndex.value = 0;
    nextTick(() => {
      searchInput.value?.focus();
    });
  }
});

const handleInput = () => {
  clearTimeout(searchTimeout);
  isLoading.value = true;
  activeIndex.value = 0;

  if (query.value.length < 3) {
    results.value = {};
    isLoading.value = false;
    return;
  }

  // 1. Perform Client-Side Search (Instant)
  const q = query.value.toLowerCase();
  
  // A. Protocol / ID Detection (Extremely Performant - Regex)
  const numericMatch = q.match(/^(\d+)$/);
  const ratMatch = q.match(/^rat[\s-]?(\d+)$/i);
  const demMatch = q.match(/^dem[\s-]?(\d+)$/i);
  
  const directActions = [];

  // Se digitar apenas números (ex: "1050")
  if (numericMatch) {
      const id = numericMatch[1];
      directActions.push({
          id: `goto_rat_${id}`,
          title: `Abrir RAT #${id}`,
          subtitle: 'Navegação Direta por ID',
          url: safeRoute('rat.show', `/rat/${id}`), // Fallback manual URL if route missing
          icon: 'bolt',
          category: 'actions',
          tag: 'JUMP TO'
      });
      directActions.push({
          id: `goto_dem_${id}`,
          title: `Abrir Demanda #${id}`,
          subtitle: 'Navegação Direta por ID',
          url: safeRoute('demandas.show', `/demandas/${id}`),
          icon: 'bolt',
          category: 'actions',
          tag: 'JUMP TO'
      });
  }
  
  // Se digitar padrão específico (ex: "RAT 500")
  if (ratMatch) {
      const id = ratMatch[1];
      directActions.push({
          id: `goto_rat_specific_${id}`,
          title: `Acessar RAT #${id}`,
          subtitle: 'Ir para Relatório Técnico',
          url: safeRoute('rat.show', `/rat/${id}`),
          icon: 'document',
          category: 'actions',
          tag: 'RAT'
      });
  }

  if (demMatch) {
      const id = demMatch[1];
      directActions.push({
          id: `goto_dem_specific_${id}`,
          title: `Acessar Demanda #${id}`,
          subtitle: 'Ir para Chamado',
          url: safeRoute('demandas.show', `/demandas/${id}`),
          icon: 'checkbadge',
          category: 'actions',
          tag: 'TASK'
      });
  }

  // B. Navigation & Keyword Search
  const localResults = navigationIndex.value.filter(item => {
      return item.title.toLowerCase().includes(q) || 
             item.subtitle.toLowerCase().includes(q) ||
             item.keywords.some(k => k.includes(q));
  });

  // Group local results
  const groupedLocal = {
      actions: [...directActions, ...localResults.filter(i => i.category === 'actions')],
      navigation: localResults.filter(i => i.category === 'navigation'),
      admin: localResults.filter(i => i.category === 'admin')
  };

  results.value = groupedLocal; // Show local immediately

  // 2. Perform Backend Search (Debounced)
  // Only if backend is reachable (we wrap in try/catch)
  searchTimeout = setTimeout(async () => {
    try {
      const response = await window.axios.get(route('global.search'), {
        params: { q: query.value },
        timeout: 8000,
      });
      const r = response.data.results ?? {};
      const dbResults = [
        ...(r.decretacoes ?? []),
        ...(r.rat        ?? []),
        ...(r.demandas   ?? []),
        ...(r.pae        ?? []),
      ];
      results.value = { ...results.value, db_results: dbResults };
    } catch (error) {
      // Falha silenciosa — resultados locais continuam visíveis
    } finally {
      isLoading.value = false;
    }
  }, 300);
};

const navigateResults = (direction) => {
  const total = flattenedResults.value.length;
  if (total === 0) return;

  activeIndex.value = (activeIndex.value + direction + total) % total;
  scrollToActive();
};

const isActive = (item) => {
    if (flattenedResults.value.length === 0) return false;
    const activeItem = flattenedResults.value[activeIndex.value];
    return activeItem && activeItem.id === item.id;
};

const setActive = (item) => {
    const index = flattenedResults.value.findIndex(i => i.id === item.id);
    if (index !== -1) activeIndex.value = index;
};

const scrollToActive = () => {
  const activeEl = document.querySelector('.bg-blue-600.text-white');
  if(activeEl) {
      activeEl.scrollIntoView({ block: 'nearest' });
  }
};

const selectResult = (item = null) => {
  const target = item || flattenedResults.value[activeIndex.value];
  if (!target) return;

  if (target.url) {
      if (target.method === 'post') {
          router.post(target.url);
      } else if (target.url.startsWith('http')) {
        window.location.href = target.url;
      } else {
        router.visit(target.url);
      }
    close();
  }
};

const executeAction = (action) => {
    selectResult(action);
};

const close = () => {
  emit('close');
};

const getCategoryLabel = (cat) => {
     const labels = {
        actions: 'Ações',
        navigation: 'Navegação',
        admin: 'Administração',
        db_results: 'Registros do Sistema'
    };
    return labels[cat] || cat;
};
</script>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.3);
  border-radius: 20px;
}
</style>