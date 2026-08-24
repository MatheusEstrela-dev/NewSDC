<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6" :default-collapsed="true">
    <FilterField
      label="Buscar"
      type="text"
      :model-value="local.buscar"
      placeholder="Protocolo ou município"
      @update:model-value="local.buscar = $event"
    />

    <FilterField
      label="Situação"
      type="select"
      :model-value="local.status"
      :options="statusOpcoes"
      placeholder="Todas as situações"
      @update:model-value="local.status = $event"
    />

    <!-- Perfil municipal ja ve so o proprio municipio: o select nao filtraria nada. -->
    <FilterField
      v-if="!ocultarMunicipio"
      label="Município"
      type="select"
      :model-value="local.municipio_id"
      :options="municipioOptions"
      placeholder="Todos os municípios"
      @update:model-value="local.municipio_id = $event"
    />

    <div>
      <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Criação — Data inicial</span>
      <DatePicker v-model="local.data_inicio" />
    </div>
    <div>
      <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Criação — Data final</span>
      <DatePicker v-model="local.data_fim" />
    </div>

    <div class="md:col-span-2 lg:col-span-4 flex items-end justify-end pt-1">
      <FilterActions @search="$emit('apply', { ...local })" @clear="limpar" />
    </div>
  </FilterSection>
</template>

<script setup>
import { computed, reactive } from 'vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  statusOpcoes: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
  ocultarMunicipio: { type: Boolean, default: false },
});

const emit = defineEmits(['apply', 'clear']);

const municipioOptions = computed(() =>
  props.municipios.map((m) => ({ value: m.id, label: `${m.nome} / ${m.uf}` }))
);

const local = reactive({
  buscar: props.filters.buscar ?? '',
  status: props.filters.status ?? '',
  municipio_id: props.filters.municipio_id ?? '',
  data_inicio: props.filters.data_inicio ?? '',
  data_fim: props.filters.data_fim ?? '',
});

function limpar() {
  local.buscar = '';
  local.status = '';
  local.municipio_id = '';
  local.data_inicio = '';
  local.data_fim = '';
  emit('clear');
}
</script>
