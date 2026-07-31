<template>
  <!--
    Card padrao do bloco de paginacao (visual de referencia: PMDA/planos).

    Empilha no mobile: em telas estreitas o texto "Mostrando X ate Y" e a fileira de
    numeros competiam pela mesma linha e o bloco estourava a largura do card.
  -->
  <div class="mt-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700/50 dark:bg-slate-900/60">
    <Text size="sm" color="muted" class="text-center sm:text-left">
      Mostrando {{ start }} até {{ end }} de {{ total }} resultados
    </Text>

    <div class="flex flex-wrap items-center justify-center gap-2">
      <Button
        variant="secondary"
        size="sm"
        :disabled="!canGoPrevious"
        @click="handlePrevious"
      >
        Anterior
      </Button>

      <!-- Os numeros quebram linha em vez de empurrar os botoes fora do card. -->
      <div class="flex flex-wrap items-center justify-center gap-1">
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
        :disabled="!canGoNext"
        @click="handleNext"
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

// Default pagination values when no data
const currentPage = computed(() => props.pagination?.current_page ?? 1);
const perPage = computed(() => props.pagination?.per_page ?? 15);
const total = computed(() => props.pagination?.total ?? 0);
const lastPage = computed(() => props.pagination?.last_page ?? 1);

const start = computed(() => {
  if (total.value === 0) return 0;
  return (currentPage.value - 1) * perPage.value + 1;
});

const end = computed(() => {
  if (total.value === 0) return 0;
  return Math.min(currentPage.value * perPage.value, total.value);
});

const canGoPrevious = computed(() => currentPage.value > 1);
const canGoNext = computed(() => currentPage.value < lastPage.value);

const visiblePages = computed(() => {
  const current = currentPage.value;
  const last = lastPage.value;
  
  if (last <= 1) return [1];
  
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
  } else if (last > 1) {
    rangeWithDots.push(last);
  }
  
  return rangeWithDots.filter((v, i, a) => a.indexOf(v) === i);
});

function getPageButtonClasses(page) {
  const base = 'px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200';
  const isActive = page === currentPage.value;
  
  if (page === '...') {
    return `${base} text-slate-500 cursor-default`;
  }
  
  if (isActive) {
    return `${base} bg-blue-600 text-white`;
  }
  
  return `${base} text-slate-400 hover:text-white hover:bg-slate-700/50 cursor-pointer`;
}

function handlePageClick(page) {
  if (page !== '...' && page !== currentPage.value) {
    emit('page-change', page);
  }
}

function handlePrevious() {
  if (canGoPrevious.value) {
    emit('page-change', currentPage.value - 1);
  }
}

function handleNext() {
  if (canGoNext.value) {
    emit('page-change', currentPage.value + 1);
  }
}
</script>


