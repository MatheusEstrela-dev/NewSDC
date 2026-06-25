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

    <FilterField
      label="Município"
      type="select"
      :model-value="local.municipio_id"
      :options="municipioOptions"
      placeholder="Todos os municípios"
      @update:model-value="local.municipio_id = $event"
    />

    <FormDateRange
      label="Período (Criação)"
      start-label="Data Inicial"
      end-label="Data Final"
      :model-value="{ start: local.data_inicio, end: local.data_fim }"
      label-size="sm"
      @update:model-value="(v) => { local.data_inicio = v.start; local.data_fim = v.end; }"
    />

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
import FormDateRange from '@/Components/Molecules/Form/FormDateRange.vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  statusOpcoes: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
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
