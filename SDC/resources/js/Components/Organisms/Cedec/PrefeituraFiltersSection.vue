<template>
  <!--
    FilterSection e o componente CANONICO de secao de filtro: 28 consumidores contra
    9 do CollapsibleSection, e 11 dos 12 *FiltersSection.vue do projeto usam ele.
    CollapsibleSection seria reimplementacao de campo aqui -- o lugar dele e a secao
    de FORMULARIO da fase 4.

    default-collapsed mantem o painel fechado ao abrir a tela: aberto, empurraria a
    lista para fora da primeira dobra. Como FilterSection nao tem slot de status
    separado, o titulo carrega a contagem de filtros ativos, que e o unico jeito de
    nao esconder essa informacao sem tocar no componente canonico.
  -->
  <FilterSection :title="tituloComContagem" :columns="3" :default-collapsed="true">
    <FilterField
      label="Município"
      type="text"
      :model-value="local.busca"
      placeholder="Nome ou parte do nome"
      @update:model-value="local.busca = $event"
    />

    <FilterField
      label="REDEC"
      type="select"
      :model-value="local.redec_id"
      :options="redecs"
      placeholder="Todas"
      @update:model-value="local.redec_id = $event"
    />

    <FilterField
      label="Macrorregião"
      type="select"
      :model-value="local.macrorregiao"
      :options="macrorregioes"
      placeholder="Todas"
      @update:model-value="local.macrorregiao = $event"
    />

    <div class="md:col-span-2 lg:col-span-3 flex items-end justify-end pt-1">
      <FilterActions @search="aplicar" @clear="limpar" />
    </div>
  </FilterSection>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';

/**
 * Organismo de filtros. Nao navega e nao conhece rota: emite `apply` com o objeto de
 * filtros e a pagina decide como pedir ao servidor.
 */
const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  redecs: { type: Array, default: () => [] },
  macrorregioes: { type: Array, default: () => [] },
});

const emit = defineEmits(['apply', 'clear']);

const VAZIO = { busca: '', redec_id: '', macrorregiao: '' };

const local = reactive({ ...VAZIO, ...normalizar(props.filters) });

/**
 * O servidor devolve os filtros que aplicou. Ressincronizar e o que mantem o
 * formulario coerente quando o filtro veio de um stat card, e nao daqui.
 */
watch(
  () => props.filters,
  (novos) => Object.assign(local, VAZIO, normalizar(novos)),
  { deep: true },
);

function normalizar(filters) {
  const f = filters ?? {};

  return {
    busca: f.busca ?? '',
    redec_id: f.redec_id ?? '',
    macrorregiao: f.macrorregiao ?? '',
  };
}

/**
 * Quantos filtros estao valendo, incluindo a pendencia escolhida por stat card, que
 * nao tem campo aqui. Sem contar a pendencia, o titulo diria "Nenhum filtro aplicado"
 * com a lista ja recortada.
 */
const resumoAtivos = computed(() => {
  const doFormulario = Object.values(local).filter((valor) => valor !== '').length;
  const daPendencia = props.filters?.pendencia ? 1 : 0;
  const ativos = doFormulario + daPendencia;

  if (ativos === 0) return 'Nenhum filtro aplicado';

  return ativos === 1 ? '1 filtro aplicado' : `${ativos} filtros aplicados`;
});

const tituloComContagem = computed(() => `Filtros de pesquisa — ${resumoAtivos.value}`);

function paraFiltros() {
  return {
    busca: local.busca.trim(),
    redec_id: local.redec_id === '' ? '' : Number(local.redec_id),
    macrorregiao: local.macrorregiao,
  };
}

function aplicar() {
  emit('apply', paraFiltros());
}

function limpar() {
  Object.assign(local, VAZIO);
  emit('clear');
}
</script>
