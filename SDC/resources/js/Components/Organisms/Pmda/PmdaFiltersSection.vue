<template>
  <div class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <button
      type="button"
      class="flex w-full items-center justify-between px-4 py-3"
      @click="aberto = !aberto"
    >
      <span class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
        <FunnelIcon class="h-4 w-4 text-slate-400" />
        Filtros de Pesquisa
      </span>
      <ChevronDownIcon class="h-4 w-4 text-slate-400 transition" :class="{ 'rotate-180': aberto }" />
    </button>

    <div v-show="aberto" class="border-t border-slate-200 p-4 dark:border-slate-700/50">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Situação</span>
          <select v-model="local.status" class="w-full rounded-md border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-700">
            <option value="">Todas</option>
            <option v-for="s in statusOpcoes" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
        </label>
        <label class="block sm:col-span-2">
          <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Município</span>
          <select v-model="local.municipio_id" class="w-full rounded-md border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-700">
            <option value="">Todos</option>
            <option v-for="m in municipios" :key="m.id" :value="m.id">{{ m.nome }} / {{ m.uf }}</option>
          </select>
        </label>
      </div>
      <div class="mt-4 flex justify-end gap-2">
        <Button variant="secondary" size="sm" @click="$emit('clear')">Limpar</Button>
        <Button variant="primary" size="sm" @click="$emit('apply', local)">Aplicar</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FunnelIcon from '@/Components/Icons/FunnelIcon.vue';
import ChevronDownIcon from '@/Components/Icons/ChevronDownIcon.vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  statusOpcoes: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
});

defineEmits(['apply', 'clear']);

const aberto = ref(false);
const local = reactive({
  status: props.filters.status ?? '',
  municipio_id: props.filters.municipio_id ?? '',
});
</script>
