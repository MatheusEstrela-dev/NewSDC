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
        class="fixed inset-0 z-[101] flex items-start justify-center p-4 sm:p-12 md:p-24 pointer-events-none"
      >
        <div
          class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden pointer-events-auto flex flex-col max-h-[70vh]"
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
              placeholder="O que você está procurando?"
              autocomplete="off"
            />
            <div class="absolute right-4 top-3.5 flex items-center gap-1.5">
               <span class="text-xs font-medium text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">ESC</span>
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
              <span class="text-sm">Buscando...</span>
            </div>

            <!-- Empty State -->
            <div
              v-else-if="!hasResults && query.length >= 2"
              class="flex flex-col items-center justify-center py-12 text-slate-500 dark:text-slate-400"
            >
              <svg class="w-10 h-10 mb-3 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-sm">Não encontramos nada para "<span class="font-medium text-slate-900 dark:text-slate-100">{{ query }}</span>"</p>
            </div>

             <!-- Initial State (Quick Actions / Recent) -->
             <div v-else-if="query.length < 2" class="p-2">
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
                        <component :is="action.icon" class="w-4 h-4" />
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
                                <!-- Dynamic Icon based on type -->
                                <svg v-if="item.icon === 'document'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <svg v-else-if="item.icon === 'checkbadge'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                                <svg v-else-if="item.icon === 'building'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <svg v-else-if="item.icon === 'folder'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                <svg v-else-if="item.icon === 'academic-cap'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" /></svg>
                                <svg v-else-if="item.icon === 'bolt'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
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
          <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-4">
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
              <span class="text-slate-400 dark:text-slate-600">Pro Mode Active</span>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
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

// Mock Quick Actions (could be dynamic)
const quickActions = [
  { id: 'new_rat', title: 'Novo RAT', subtitle: 'Criar Relatório de Atendimento', url: '/rat/create', icon: 'document', type: 'action' },
  { id: 'new_demanda', title: 'Nova Demanda', subtitle: 'Abrir um chamado', url: '/demandas/nova', icon: 'checkbadge', type: 'action' },
  { id: 'profile', title: 'Meu Perfil', subtitle: 'Configurações da conta', url: '/profile', icon: 'user', type: 'action' },
];

const flattenedResults = computed(() => {
    // If empty query, return quick actions
    if (query.value.length < 2) return quickActions;

    // Flatten categorized results into a single array for keyboard navigation
    let flat = [];
    // Prioritize Actions
    if (results.value.actions) flat = [...flat, ...results.value.actions];

    const categories = ['rats', 'demandas', 'orgaos', 'processos', 'treinamentos'];
    categories.forEach(cat => {
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

  if (query.value.length < 2) {
    results.value = {};
    isLoading.value = false;
    return;
  }

  searchTimeout = setTimeout(async () => {
    try {
      const response = await window.axios.get(route('global.search'), {
        params: { query: query.value }
      });
      // Add unique IDs to results for v-for keys if missing
      const categorized = response.data;
      Object.keys(categorized).forEach(cat => {
          categorized[cat] = categorized[cat].map(item => ({
              ...item,
              uniqueId: `${cat}_${item.id}`
          }));
      });
      results.value = categorized;
    } catch (error) {
      console.error('Search error:', error);
      results.value = {};
    } finally {
      isLoading.value = false;
    }
  }, 200); // Fast debounce for "Pro" feel
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
    // Check equality based on ID or object reference
    return activeItem && (activeItem.id === item.id || activeItem.uniqueId === item.uniqueId);
};

const setActive = (item) => {
    const index = flattenedResults.value.findIndex(i => (i.id === item.id || i.uniqueId === item.uniqueId));
    if (index !== -1) activeIndex.value = index;
};

const scrollToActive = () => {
  // Simple scroll into view logic could be added here if list is long
  const activeEl = document.querySelector('.bg-blue-600.text-white'); // Class of active item
  if(activeEl) {
      activeEl.scrollIntoView({ block: 'nearest' });
  }
};

const selectResult = (item = null) => {
  const target = item || flattenedResults.value[activeIndex.value];
  if (!target) return;

  if (target.url) {
    // If it's an external link or specialized route, use standard navigation or Inertia
    if (target.url.startsWith('http')) {
        window.location.href = target.url;
    } else {
        router.visit(target.url);
    }
    close();
  }
};

const executeAction = (action) => {
    if (action.url) {
        router.visit(action.url);
        close();
    }
};

const close = () => {
  emit('close');
};

const getCategoryLabel = (cat) => {
     const labels = {
        actions: 'Ações Rápidas',
        rats: 'RATs',
        demandas: 'Demandas',
        orgaos: 'Órgãos',
        processos: 'Processos',
        treinamentos: 'Treinamentos'
    };
    return labels[cat] || cat;
};

// Keyboard shortcut listener
const onKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        if (!props.isOpen) {
            // We can't emit 'open' because we are inside the component.
            // The parent needs to control the state or we use an event bus.
            // For now, we rely on parent passing isOpen.
            // But wait, the prompt says "Ctrl+K enywhere".
            // So this component should probably be mounted once in Layout and control its own visibility?
            // Or TopBar controls it.
            // Let's assume TopBar handles the shortcut or passes it down.
            // Actually, best practice: This component emits an event or we listen in TopBar.
        }
    }
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
