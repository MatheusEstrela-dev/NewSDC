<template>
  <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between">
    <Text size="sm" color="muted">
      Mostrando {{ start }} até {{ end }} de {{ pagination.total }} resultados
    </Text>
    
    <div class="flex items-center gap-2">
      <Button
        variant="secondary"
        size="sm"
        :disabled="pagination.current_page === 1"
        @click="$emit('page-change', pagination.current_page - 1)"
      >
        Anterior
      </Button>
      
      <div class="flex items-center gap-1">
        <button
          v-for="page in visiblePages"
          :key="page"
          :class="getPageButtonClasses(page)"
          @click="handlePageClick(page)"
        >
          {{ page }}
        </button>
      </div>
      
      <Button
        variant="secondary"
        size="sm"
        :disabled="pagination.current_page === pagination.last_page"
        @click="$emit('page-change', pagination.current_page + 1)"
      >
        Próxima
      </Button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from '../../Atoms/Button/Button.vue';
import Text from '../../Atoms/Typography/Text.vue';

const props = defineProps({
  pagination: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['page-change']);

const start = computed(() => {
  if (!props.pagination) return 0;
  return (props.pagination.current_page - 1) * props.pagination.per_page + 1;
});

const end = computed(() => {
  if (!props.pagination) return 0;
  return Math.min(props.pagination.current_page * props.pagination.per_page, props.pagination.total);
});

const visiblePages = computed(() => {
  if (!props.pagination) return [];
  
  const current = props.pagination.current_page;
  const last = props.pagination.last_page;
  const delta = 2;
  
  const range = [];
  const rangeWithDots = [];
  
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i);
  }
  
  if (current - delta > 2) {
    rangeWithDots.push(1, '...');
  } else {
    rangeWithDots.push(1);
  }
  
  rangeWithDots.push(...range);
  
  if (current + delta < last - 1) {
    rangeWithDots.push('...', last);
  } else {
    rangeWithDots.push(last);
  }
  
  return rangeWithDots.filter((v, i, a) => a.indexOf(v) === i);
});

function getPageButtonClasses(page) {
  const base = 'px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200';
  const isActive = page === props.pagination?.current_page;
  
  if (page === '...') {
    return `${base} text-slate-500 cursor-default`;
  }
  
  if (isActive) {
    return `${base} bg-blue-600 text-white`;
  }
  
  return `${base} text-slate-400 hover:text-white hover:bg-slate-700/50 cursor-pointer`;
}

function handlePageClick(page) {
  if (page !== '...' && page !== props.pagination?.current_page) {
    emit('page-change', page);
  }
}
</script>

