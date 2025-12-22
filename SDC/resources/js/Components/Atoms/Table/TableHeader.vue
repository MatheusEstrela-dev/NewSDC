<template>
  <th :class="headerClasses" @click="handleSort">
    <div class="flex items-center gap-2">
      <slot />
      <span v-if="sortable" class="flex flex-col">
        <svg 
          :class="['w-3 h-3 transition-colors', sortDirection === 'asc' ? 'text-blue-400' : 'text-slate-500']"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
        </svg>
        <svg 
          :class="['w-3 h-3 transition-colors -mt-1', sortDirection === 'desc' ? 'text-blue-400' : 'text-slate-500']"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </span>
    </div>
  </th>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  align: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'center', 'right'].includes(value),
  },
  sortable: {
    type: Boolean,
    default: false,
  },
  sortDirection: {
    type: String,
    default: null,
    validator: (value) => value === null || ['asc', 'desc'].includes(value),
  },
});

const emit = defineEmits(['sort']);

function handleSort() {
  if (props.sortable) {
    const newDirection = props.sortDirection === 'asc' ? 'desc' : 'asc';
    emit('sort', newDirection);
  }
}

const alignClasses = {
  left: 'text-left',
  center: 'text-center',
  right: 'text-right',
};

const headerClasses = computed(() => {
  const base = 'px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-900/50';
  const sortableClass = props.sortable ? 'cursor-pointer hover:bg-slate-800/50 select-none' : '';
  
  return [
    base,
    alignClasses[props.align],
    sortableClass,
  ].join(' ');
});
</script>

