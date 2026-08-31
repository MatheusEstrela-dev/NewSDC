<template>
  <div class="w-full rat-tabs-container">
    <!-- Seletor de Abas - Mobile Optimized -->
    <div class="rat-tabs-wrapper bg-slate-100 dark:bg-slate-800/30 rounded-xl p-1.5 mb-6 relative">
      <!-- Fade indicators for scroll -->
      <div
        v-if="showLeftFade"
        class="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-slate-100 dark:from-slate-800/80 to-transparent z-10 pointer-events-none rounded-l-xl"
      ></div>
      <div
        v-if="showRightFade"
        class="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-slate-100 dark:from-slate-800/80 to-transparent z-10 pointer-events-none rounded-r-xl"
      ></div>

      <nav
        ref="tabsNav"
        class="flex gap-1 overflow-x-auto hide-scrollbar scroll-smooth snap-x snap-mandatory"
        aria-label="Tabs"
        @scroll="handleScroll"
      >
        <button
          v-for="tab in visibleTabs"
          :key="tab.id"
          v-bind="!tab.disabled ? { onClick: () => handleTabClick(tab) } : {}"
          :class="getTabClass(tab.id, tab.disabled)"
          :disabled="tab.disabled || undefined"
          :style="tab.disabled ? { pointerEvents: 'none' } : {}"
          :title="tab.disabled ? 'Salve a aba atual para navegar' : tab.label"
          type="button"
          class="snap-start"
        >
          <component :is="tab.icon" class="w-5 h-5 sm:w-4 sm:h-4 flex-shrink-0" />
          <!--
            A aba ATIVA sempre mostra o nome; as inativas ficam so no icone
            abaixo de `sm`.

            Antes o rotulo era `hidden sm:inline` para todas, e no telefone o
            wizard virava uma fileira de cinco icones sem legenda: nao havia
            como saber em que etapa se esta, nem o que as outras sao. Mostrar
            todos os nomes nao serve -- a fileira passaria de tres telas de
            largura. Nomear so a ativa cabe e responde a pergunta que importa.
          -->
          <span
            class="whitespace-nowrap"
            :class="activeTab === tab.id ? 'inline' : 'hidden sm:inline'"
          >{{ tab.label }}</span>
          <span
            v-if="tab.badge && !tab.disabled"
            class="ml-1 sm:ml-1.5 px-1.5 sm:px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0 min-w-[1.25rem] text-center"
            :class="activeTab === tab.id ? 'bg-blue-500/20 text-blue-600 dark:text-blue-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400'"
          >
            {{ tab.badge }}
          </span>
          <!-- Ícone de cadeado para abas bloqueadas -->
          <svg
            v-if="tab.disabled"
            class="w-3 h-3 ml-1 flex-shrink-0 opacity-70"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </button>
      </nav>
    </div>

    <!-- Conteudo das Abas -->
    <div class="rat-tabs-content">
      <slot :active-tab="activeTab" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  activeTab: {
    type: Number,
    default: 1,
  },
  tabs: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['tab-change']);

const tabsNav = ref(null);
const showLeftFade = ref(false);
const showRightFade = ref(false);

const visibleTabs = computed(() => {
  return props.tabs.filter(tab => !tab.hidden);
});

function handleTabClick(tab) {
  if (tab.disabled) return;
  emit('tab-change', tab.id);
}

function getTabClass(tabId, disabled) {
  const baseClass =
    'px-3 py-3 sm:px-4 sm:py-2.5 rounded-lg font-medium text-sm transition-all duration-200 flex items-center gap-1.5 sm:gap-2 outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 select-none relative whitespace-nowrap min-w-[3rem] sm:min-w-0 justify-center sm:justify-start';

  if (disabled) {
    return `${baseClass} opacity-40 cursor-not-allowed text-slate-400 dark:text-slate-500`;
  }

  if (props.activeTab === tabId) {
    return `${baseClass} cursor-pointer text-blue-600 dark:text-blue-400 bg-white dark:bg-blue-500/10 border-b-2 border-blue-500 dark:border-blue-400 shadow-sm dark:shadow-none`;
  }

  return `${baseClass} cursor-pointer text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/70 dark:hover:bg-slate-700/50`;
}

function handleScroll() {
  if (!tabsNav.value) return;

  const { scrollLeft, scrollWidth, clientWidth } = tabsNav.value;
  showLeftFade.value = scrollLeft > 10;
  showRightFade.value = scrollLeft < scrollWidth - clientWidth - 10;
}

function checkOverflow() {
  if (!tabsNav.value) return;

  const { scrollWidth, clientWidth } = tabsNav.value;
  const hasOverflow = scrollWidth > clientWidth;

  if (hasOverflow) {
    handleScroll();
  } else {
    showLeftFade.value = false;
    showRightFade.value = false;
  }
}

onMounted(() => {
  checkOverflow();
  window.addEventListener('resize', checkOverflow);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkOverflow);
});
</script>

<style scoped>
.rat-tabs-container {
  width: 100%;
  position: relative;
}

.rat-tabs-wrapper {
  position: relative;
  z-index: 10;
}

.rat-tabs-content {
  position: relative;
  z-index: 1;
  width: 100%;
}

/* Garantir que os botoes das abas nao quebrem */
.rat-tabs-wrapper nav {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  -webkit-overflow-scrolling: touch;
}

.rat-tabs-wrapper button {
  flex-shrink: 0;
  min-width: fit-content;
}

/* Botão desabilitado: garantir cursor correto (atributo disabled reseta para default em alguns browsers) */
.rat-tabs-wrapper button:disabled {
  cursor: not-allowed;
}

/* Mobile: icones maiores e mais espacamento para touch */
@media (max-width: 639px) {
  .rat-tabs-wrapper nav {
    gap: 0.25rem;
    padding: 0.25rem;
  }

  .rat-tabs-wrapper button {
    min-width: 3rem;
    min-height: 2.75rem;
  }
}

/* Scroll snap behavior */
.snap-x {
  scroll-snap-type: x mandatory;
}

.snap-mandatory {
  scroll-snap-type: x mandatory;
}

.snap-start {
  scroll-snap-align: start;
}
</style>
