<template>
  <section class="mb-6 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
      <label class="block">
        <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Busca</span>
        <input
          v-model="localFilters.search"
          type="search"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          placeholder="Nome, patrimonio ou responsavel"
          @keyup.enter="apply"
        />
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Categoria</span>
        <select
          v-model="localFilters.categoria"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
        >
          <option value="">Todas</option>
          <option v-for="option in filterOptions.categorias || []" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Situacao</span>
        <select
          v-model="localFilters.situacao"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
        >
          <option value="">Todas</option>
          <option v-for="option in filterOptions.situacoes || []" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </label>

      <div class="flex items-end gap-2">
        <Button variant="primary" size="md" class="flex-1" @click="apply">
          Filtrar
        </Button>
        <Button variant="secondary" size="md" @click="clear">
          Limpar
        </Button>
      </div>
    </div>
  </section>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import { ref, watch } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:filters', 'apply', 'clear']);

const localFilters = ref({ ...props.filters });

watch(
  () => props.filters,
  (filters) => {
    localFilters.value = { ...filters };
  },
  { deep: true }
);

watch(
  localFilters,
  (filters) => {
    emit('update:filters', { ...filters });
  },
  { deep: true }
);

function apply() {
  emit('apply', { ...localFilters.value });
}

function clear() {
  localFilters.value = {};
  emit('clear');
}
</script>
