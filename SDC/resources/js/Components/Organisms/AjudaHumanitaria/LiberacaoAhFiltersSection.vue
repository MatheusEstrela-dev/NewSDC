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
      label="Situação"
      type="select"
      :model-value="local.status"
      :options="opcoesStatus"
      placeholder="Todas"
      @update:model-value="local.status = $event"
    />

    <FilterField
      class="md:col-span-2"
      label="Município, beneficiário ou código"
      type="text"
      :model-value="local.busca"
      placeholder="Ex: Divinópolis, 3654"
      @update:model-value="local.busca = $event"
    />

    <FormDateRange
      class="md:col-span-2"
      label="Data da liberação"
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
  /** Apenas depositos que aparecem em alguma liberacao. */
  depositos: { type: Array, default: () => [] },
  opcoesStatus: { type: Array, default: () => [] },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

/**
 * Estado local, que so sobe no clique de Aplicar.
 *
 * FilterField emite a cada tecla; repassar adiante dispararia uma navegacao por
 * caractere no campo de busca. Como a secao tem botao proprio, a consulta
 * acontece quando o usuario decide.
 */
const local = reactive({
  deposito_id: props.filters.deposito_id ?? '',
  status: props.filters.status ?? '',
  busca: props.filters.busca ?? '',
  data_inicio: props.filters.data_inicio ?? '',
  data_fim: props.filters.data_fim ?? '',
});

watch(
  () => props.filters,
  (novos) => {
    local.deposito_id = novos.deposito_id ?? '';
    local.status = novos.status ?? '';
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
  local.status = '';
  local.busca = '';
  local.data_inicio = '';
  local.data_fim = '';
  emit('filter-reset');
}
</script>
