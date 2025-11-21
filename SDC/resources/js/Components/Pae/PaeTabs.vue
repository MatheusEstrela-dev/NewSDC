<template>
  <div class="w-full">
    <!-- Seletor de Abas -->
    <div class="border-b border-slate-700 overflow-x-auto hide-scrollbar">
      <nav class="-mb-px flex space-x-8 min-w-max" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="$emit('tab-change', tab.id)"
          :class="getTabClass(tab.id)"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
          <span v-if="tab.badge" class="ml-1 px-1.5 py-0.5 rounded-full bg-slate-700 text-xs text-slate-300">
            {{ tab.badge }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Conteúdo das Abas -->
    <div class="py-6">
      <slot :active-tab="activeTab" />
    </div>
  </div>
</template>

<script setup>
import DocumentTextIcon from '../Icons/DocumentTextIcon.vue';
import ClockIcon from '../Icons/ClockIcon.vue';
import UsersIcon from '../Icons/UsersIcon.vue';
import BuildingOfficeIcon from '../Icons/BuildingOfficeIcon.vue';

const props = defineProps({
  activeTab: {
    type: Number,
    default: 1,
  },
  tabs: {
    type: Array,
    default: () => [
      { id: 1, label: 'Formulário PAE', icon: DocumentTextIcon },
      { id: 2, label: 'Histórico', icon: ClockIcon, badge: null },
      { id: 3, label: 'CCPAE', icon: UsersIcon },
      { id: 4, label: 'Empreendedor', icon: BuildingOfficeIcon },
    ],
  },
});

defineEmits(['tab-change']);

function getTabClass(tabId) {
  const baseClass =
    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 flex items-center gap-2 outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 rounded-t cursor-pointer select-none';

  if (props.activeTab === tabId) {
    return `${baseClass} border-blue-500 text-blue-400 bg-gradient-to-t from-blue-500/10 to-transparent`;
  }

  return `${baseClass} border-transparent text-slate-500 hover:text-slate-300 hover:border-slate-700`;
}
</script>

