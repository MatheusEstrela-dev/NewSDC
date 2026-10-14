<template>
  <FilterSection title="Filtros de Pesquisa" :columns="5" :default-collapsed="false" class="mb-6">
    <div>
      <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Busca / leitura de código</span>
      <div class="relative">
        <!-- Leitor de codigo de barras/QR em modo teclado escreve aqui e envia Enter. -->
        <input
          v-model="local.busca"
          type="search"
          inputmode="search"
          autocomplete="off"
          placeholder="Nome, SKU, patrimônio ou lote"
          class="w-full rounded-md border-slate-300 pr-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
          @keyup.enter="aplicar"
        />
        <QrCodeIcon class="pointer-events-none absolute right-2.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" title="Compatível com leitor de QR/código de barras" />
      </div>
    </div>
    <FilterField label="Categoria" type="select" :model-value="local.categoria" :options="filterOptions.categorias ?? []" placeholder="Todas" @update:model-value="local.categoria = $event" />
    <FilterField label="Almoxarifado" type="select" :model-value="local.almoxarifado_id" :options="filterOptions.almoxarifados ?? []" placeholder="Todos" @update:model-value="local.almoxarifado_id = $event" />
    <FilterField label="Validade" type="select" :model-value="local.validade" :options="filterOptions.validade ?? []" placeholder="Todas" @update:model-value="local.validade = $event" />
    <FilterField label="Situação" type="select" :model-value="local.status" :options="filterOptions.status ?? []" placeholder="Todas" @update:model-value="local.status = $event" />
    <div class="flex items-end justify-end md:col-span-5">
      <FilterActions @search="aplicar" @clear="limpar" />
    </div>
  </FilterSection>
</template>

<script setup>
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import { QrCodeIcon } from '@heroicons/vue/24/outline';
import { reactive, watch } from 'vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  filterOptions: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['apply', 'clear']);

const VAZIO = { busca: '', categoria: '', almoxarifado_id: '', validade: '', status: '' };
const local = reactive({ ...VAZIO, ...props.filters });

watch(() => props.filters, (f) => Object.assign(local, VAZIO, f), { deep: true });

function aplicar() {
  emit('apply', Object.fromEntries(Object.entries(local).filter(([, v]) => v !== '' && v !== null && v !== undefined)));
}

function limpar() {
  Object.assign(local, VAZIO);
  emit('clear');
}
</script>
