<template>
  <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
    <!-- Total de Eventos - atalho de filtro rapido: limpa os filtros.
         Sem rateio ECP/SE/N1: o card mistura Registros (sempre N1) com
         Decretacoes, e o rateio nao respondia nada. No lugar dele, a
         composicao que de fato define o total. -->
    <StatCardWithBreakdown
      title="Total de Eventos"
      :value="statistics.totalEventos"
      :show-breakdown="false"
      :composicao="composicaoTotal"
      nota="Soma dos eventos registrados e das decretações realizadas no período."
      variant="info"
      :icon="BoltIcon"
      @click="handleFilter({})"
    />

    <!-- Registros - atalho de filtro rapido (tipo_lancamento=registro).
         Registro e sempre N1, entao o rateio exibia ECP 0 / SE 0 e induzia
         a leitura de que o card estava vazio. -->
    <StatCardWithBreakdown
      title="Registros"
      :value="statistics.registros"
      :show-breakdown="false"
      :composicao="composicaoRegistros"
      nota="Ocorrências registradas sem decretação de emergência ou calamidade, classificadas como Nível 1 (N1)."
      variant="info"
      :icon="ClipboardDocumentListIcon"
      @click="handleFilter({ tipo_lancamento: 'registro' })"
    />

    <!-- Decretacoes - atalho de filtro rapido (tipo_lancamento=decretacao) -->
    <StatCardWithBreakdown
      title="Decretacoes"
      :value="statistics.decretacoes"
      :ecp="statistics.decretacoesEcp"
      :se="statistics.decretacoesSe"
      :n1="statistics.decretacoesN1"
      nota="Um município pode ter mais de uma decretação no período."
      variant="info"
      :icon="DocumentTextIcon"
      @click="handleFilter({ tipo_lancamento: 'decretacao' })"
    />

    <!-- Municipios Atingidos: unico agregado sem recorte equivalente na
         listagem de processos, portanto nao filtravel. Tambem nao exibe
         leitura por municipio, pois ja e medido nesse grao. -->
    <StatCardWithBreakdown
      title="Municipios Atingidos"
      :value="statistics.municipiosAtingidos"
      :ecp="statistics.municipiosAtingidosEcp"
      :se="statistics.municipiosAtingidosSe"
      :n1="statistics.municipiosAtingidosN1"
      nota="Cada município conta uma vez, mesmo com mais de um decreto — por isso o rateio por tipo pode somar mais que o total."
      variant="warning"
      :icon="MapIcon"
      :clickable="false"
    />

    <!-- Decretacoes Vigentes - atalho de filtro rapido (vigencia_status=vigente) -->
    <StatCardWithBreakdown
      title="Decretacoes Vigentes"
      :value="statistics.decretacoesVigentes"
      :ecp="statistics.decretacoesVigentesEcp"
      :se="statistics.decretacoesVigentesSe"
      :n1="statistics.decretacoesVigentesN1"
      nota="Decretações vigentes são aquelas que estão em pleno vigor e produzindo efeitos jurídicos imediatos."
      nota-align="right"
      variant="success"
      :icon="CheckCircleIcon"
      @click="handleFilter({ vigencia_status: 'vigente' })"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatCardWithBreakdown from '@/Components/Molecules/Statistics/StatCardWithBreakdown.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

const props = defineProps({
  statistics: {
    type: Object,
    default: () => ({
      totalEventos: 0,
      totalEventosEcp: 0,
      totalEventosSe: 0,
      totalEventosN1: 0,
      totalEventosPorMunicipio: 0,
      registros: 0,
      registrosEcp: 0,
      registrosSe: 0,
      registrosN1: 0,
      registrosPorMunicipio: 0,
      decretacoes: 0,
      decretacoesEcp: 0,
      decretacoesSe: 0,
      decretacoesN1: 0,
      decretacoesPorMunicipio: 0,
      municipiosAtingidos: 0,
      municipiosAtingidosEcp: 0,
      municipiosAtingidosSe: 0,
      municipiosAtingidosN1: 0,
      decretacoesVigentes: 0,
      decretacoesVigentesEcp: 0,
      decretacoesVigentesSe: 0,
      decretacoesVigentesN1: 0,
      decretacoesVigentesPorMunicipio: 0,
    }),
  },
});

const emit = defineEmits(['filter']);

// Composicao derivada dos agregados que a pagina ja recebe: nao ha ida extra
// ao backend so para desenhar a barra.
const composicaoTotal = computed(() => ({
  base: props.statistics.totalEventos ?? 0,
  partes: [
    { rotulo: 'Registros', valor: props.statistics.registros ?? 0, classe: 'bg-cyan-400' },
    { rotulo: 'Decretacoes', valor: props.statistics.decretacoes ?? 0, classe: 'bg-cyan-600' },
  ],
}));

const composicaoRegistros = computed(() => ({
  base: props.statistics.totalEventos ?? 0,
  partes: [
    { rotulo: 'Do total', valor: props.statistics.registros ?? 0, classe: 'bg-cyan-400' },
  ],
}));


// Emite o conjunto de filtros que o card representa. Objeto vazio limpa os
// atalhos (card Total), conforme o padrao de stat cards como filtro rapido.
function handleFilter(patch) {
  emit('filter', patch);
}
</script>
