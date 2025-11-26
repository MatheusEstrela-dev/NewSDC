<template>
  <div class="bg-slate-800/80 backdrop-blur-sm rounded-xl shadow-xl border border-slate-700/50 overflow-hidden transition-all duration-200 hover:border-slate-600/50">
    <!-- Header -->
    <div class="px-5 py-4 border-b border-slate-700/50 bg-gradient-to-r from-slate-800 to-slate-800/50">
      <h3 class="text-base font-semibold text-white flex items-center gap-3">
        <!-- Ícone dinâmico -->
        <div v-if="icon" class="p-2 rounded-lg" :class="iconBgClass">
          <svg v-if="icon === 'clock'" class="w-4 h-4" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else-if="icon === 'file-text'" class="w-4 h-4" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <svg v-else-if="icon === 'settings'" class="w-4 h-4" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <svg v-else class="w-4 h-4" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </div>
        <!-- Barra vertical decorativa -->
        <span class="w-1 h-6 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full"></span>
        <span>{{ title }}</span>
      </h3>
    </div>
    <!-- Content -->
    <div class="p-5">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  icon: {
    type: [String, Object],
    default: null,
  },
});

const iconBgClass = computed(() => {
  switch (props.icon) {
    case 'clock':
      return 'bg-blue-500/20';
    case 'file-text':
      return 'bg-emerald-500/20';
    case 'settings':
      return 'bg-purple-500/20';
    default:
      return 'bg-slate-500/20';
  }
});

const iconClass = computed(() => {
  switch (props.icon) {
    case 'clock':
      return 'text-blue-400';
    case 'file-text':
      return 'text-emerald-400';
    case 'settings':
      return 'text-purple-400';
    default:
      return 'text-slate-400';
  }
});
</script>

