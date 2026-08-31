<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Depósito"
      type="select"
      :model-value="local.deposito_id"
      :options="opcoesDeposito"
      placeholder="Todos os depósitos"
      @update:model-value="local.deposito_id = $event"
    />

    <FilterField
      label="Tipo de lançamento"
      type="select"
      :model-value="local.tipo"
      :options="opcoesTipo"
      placeholder="Todos os tipos"
      @update:model-value="local.tipo = $event"
    />

    <FilterField
      class="md:col-span-2"
      label="Material"
      type="text"
      :model-value="local.busca"
      placeholder="Ex: CESTA BASICA"
      @update:model-value="local.busca = $event"
    />

    <FormDateRange
      class="md:col-span-2"
      label="Data do movimento"
      start-label="De"
      end-label="Até"
      :model-value="{ start: local.data_inicio, end: local.data_fim }"
      @update:model-value="aplicarPeriodo"
    />

    <div class="md:col-span-2 flex items-end justify-end gap-2">
      <FilterActions @search="aplicar" @clear="limpar" />
    </div>
  </FilterSection>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FormDateRange from '@/Components/Molecules/Form/FormDateRange.vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  /** Somente tipos que existem no ledger; ver tiposEmUso() no controller. */
  opcoesTipo: { type: Array, default: () => [] },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

// O sentido (entrada/saida) fica so nos cartoes: e recorte de leitura rapida,
// e repetir como campo daria dois lugares para dizer a mesma coisa.
const local = reactive({
  deposito_id: props.filters.deposito_id ?? '',
  tipo: props.filters.tipo ?? '',
  busca: props.filters.busca ?? '',
  data_inicio: props.filters.data_inicio ?? '',
  data_fim: props.filters.data_fim ?? '',
});

watch(
  () => props.filters,
  (novos) => {
    local.deposito_id = novos.deposito_id ?? '';
    local.tipo = novos.tipo ?? '';
    local.busca = novos.busca ?? '';
    local.data_inicio = novos.data_inicio ?? '';
    local.data_fim = novos.data_fim ?? '';
  },
);

const opcoesDeposito = computed(() => props.depositos.map((d) => ({
  value: d.id,
  label: `${d.sigla} — ${d.nome}`,
})));

function aplicarPeriodo(intervalo) {
  local.data_inicio = intervalo.start ?? '';
  local.data_fim = intervalo.end ?? '';
}

function aplicar() {
  emit('filter-change', { ...local });
}

function limpar() {
  local.deposito_id = '';
  local.tipo = '';
  local.busca = '';
  local.data_inicio = '';
  local.data_fim = '';
  emit('filter-reset');
}
</script>
