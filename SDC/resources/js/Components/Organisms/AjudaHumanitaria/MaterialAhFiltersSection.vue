<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      class="md:col-span-2"
      label="Nome, descrição ou código"
      type="text"
      :model-value="local.busca"
      placeholder="Ex: CESTA BASICA, 1"
      @update:model-value="local.busca = $event"
    />

    <FilterField
      label="Unidade"
      type="select"
      :model-value="local.unidade"
      :options="opcoesUnidade"
      placeholder="Todas as unidades"
      @update:model-value="local.unidade = $event"
    />

    <FilterField
      label="Situação"
      type="select"
      :model-value="local.situacao"
      :options="OPCOES_SITUACAO"
      placeholder="Todas"
      @update:model-value="local.situacao = $event"
    />

    <div class="md:col-span-4 flex items-end justify-end gap-2">
      <FilterActions @search="aplicar" @clear="limpar" />
    </div>
  </FilterSection>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';

// Os mesmos recortes dos cartoes: quem prefere o campo encontra a opcao aqui,
// e o estado da tela e um so.
const OPCOES_SITUACAO = [
  { value: 'disponivel', label: 'Disponível para pedido' },
  { value: 'indisponivel', label: 'Indisponível' },
  { value: 'com_saldo', label: 'Com saldo em depósito' },
  { value: 'sem_saldo', label: 'Ofertado sem estoque' },
];

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  /** Unidades vindas do proprio catalogo, nao de uma lista fixa em codigo. */
  unidades: { type: Array, default: () => [] },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

const local = reactive({
  busca: props.filters.busca ?? '',
  unidade: props.filters.unidade ?? '',
  situacao: props.filters.situacao ?? '',
});

watch(
  () => props.filters,
  (novos) => {
    local.busca = novos.busca ?? '';
    local.unidade = novos.unidade ?? '';
    local.situacao = novos.situacao ?? '';
  },
);

const opcoesUnidade = computed(() => props.unidades.map((u) => ({ value: u, label: u })));

function aplicar() {
  emit('filter-change', { ...local });
}

function limpar() {
  local.busca = '';
  local.unidade = '';
  local.situacao = '';
  emit('filter-reset');
}
</script>
