<template>
  <div class="rat-section-card" :class="{ 'rat-section-collapsed': !isExpanded }">
    <!-- Header clicavel -->
    <div
      class="rat-section-header cursor-pointer select-none"
      @click="toggle"
      role="button"
      :aria-expanded="isExpanded"
      tabindex="0"
      @keydown.enter="toggle"
      @keydown.space.prevent="toggle"
    >
      <div :class="['rat-section-icon', iconClass]">
        <slot name="icon">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </slot>
      </div>

      <div class="flex-1 min-w-0">
        <h3 class="rat-section-title">{{ title }}</h3>
        <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5 truncate">{{ subtitle }}</p>
      </div>

      <!-- Indicador de status quando colapsado -->
      <div v-if="!isExpanded && statusText" class="hidden sm:block text-xs text-slate-400 mr-2">
        {{ statusText }}
      </div>

      <!-- Chevron animado -->
      <div class="flex-shrink-0 ml-2">
        <svg
          class="w-5 h-5 text-slate-400 transition-transform duration-200"
          :class="{ 'rotate-180': isExpanded }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </div>

    <!-- Conteudo colapsavel com animacao -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      leave-active-class="transition-all duration-200 ease-in"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-[2000px]"
      leave-from-class="opacity-100 max-h-[2000px]"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-show="isExpanded" class="rat-section-content overflow-hidden">
        <slot></slot>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { useCollapsible } from '@/composables/rat';

const props = defineProps({
  sectionId: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  iconClass: {
    type: String,
    default: 'rat-section-icon-default',
  },
  defaultExpanded: {
    type: Boolean,
    default: true,
  },
  statusText: {
    type: String,
    default: '',
  },
});

const { isExpanded, toggle } = useCollapsible(props.sectionId, props.defaultExpanded);
</script>

<style scoped>
.rat-section-collapsed {
  @apply bg-slate-50/50 dark:bg-slate-900/30;
}

.rat-section-collapsed .rat-section-header {
  @apply mb-0 pb-0 border-b-0;
}
</style>
