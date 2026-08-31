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
      label="Fonte de recurso"
      type="select"
      :model-value="local.fonte_id"
      :options="opcoesFonte"
      placeholder="Todas as fontes"
      @update:model-value="local.fonte_id = $event"
    />

    <FilterField
      class="md:col-span-2"
      label="Nota fiscal, material, código ou observação"
      type="text"
      :model-value="local.busca"
      placeholder="Ex: MASCARA, 0840095"
      @update:model-value="local.busca = $event"
    />

    <FormDateRange
      class="md:col-span-2"
      label="Data de recebimento"
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
  /**
   * Nao ha filtro de fornecedor: aju_produto nao registra fornecedor, entao a
   * coluna fica nula em toda a carga e o filtro nunca devolveria nada.
   */
  fontes: { type: Array, default: () => [] },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

const local = reactive({
  deposito_id: props.filters.deposito_id ?? '',
  fonte_id: props.filters.fonte_id ?? '',
  busca: props.filters.busca ?? '',
  data_inicio: props.filters.data_inicio ?? '',
  data_fim: props.filters.data_fim ?? '',
});

watch(
  () => props.filters,
  (novos) => {
    local.deposito_id = novos.deposito_id ?? '';
    local.fonte_id = novos.fonte_id ?? '';
    local.busca = novos.busca ?? '';
    local.data_inicio = novos.data_inicio ?? '';
    local.data_fim = novos.data_fim ?? '';
  },
);

const opcoesDeposito = computed(() => props.depositos.map((d) => ({
  value: d.id,
  label: `${d.sigla} — ${d.nome}`,
})));

const opcoesFonte = computed(() => props.fontes.map((f) => ({ value: f.id, label: f.nome })));

function aplicarPeriodo(intervalo) {
  local.data_inicio = intervalo.start ?? '';
  local.data_fim = intervalo.end ?? '';
}

function aplicar() {
  emit('filter-change', { ...local });
}

function limpar() {
  local.deposito_id = '';
  local.fonte_id = '';
  local.busca = '';
  local.data_inicio = '';
  local.data_fim = '';
  emit('filter-reset');
}
</script>
