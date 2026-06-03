<template>
  <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
    <!-- Total de Eventos -->
    <StatCardWithBreakdown
      title="Total de Eventos"
      :value="statistics.totalEventos"
      :ecp="statistics.totalEventosEcp"
      :se="statistics.totalEventosSe"
      :n1="statistics.totalEventosN1"
      variant="info"
      :icon="BoltIcon"
      @click="handleFilter('eventos')"
    />

    <!-- Registros -->
    <StatCardWithBreakdown
      title="Registros"
      :value="statistics.registros"
      :ecp="statistics.registrosEcp"
      :se="statistics.registrosSe"
      :n1="statistics.registrosN1"
      variant="info"
      :icon="ClipboardDocumentListIcon"
      @click="handleFilter('registros')"
    />

    <!-- Decretacoes -->
    <StatCardWithBreakdown
      title="Decretacoes"
      :value="statistics.decretacoes"
      :ecp="statistics.decretacoesEcp"
      :se="statistics.decretacoesSe"
      :n1="statistics.decretacoesN1"
      variant="info"
      :icon="DocumentTextIcon"
      @click="handleFilter('decretacoes')"
    />

    <!-- Municipios Atingidos -->
    <StatCardWithBreakdown
      title="Municipios Atingidos"
      :value="statistics.municipiosAtingidos"
      :ecp="statistics.municipiosAtingidosEcp"
      :se="statistics.municipiosAtingidosSe"
      :n1="statistics.municipiosAtingidosN1"
      variant="warning"
      :icon="MapIcon"
      @click="handleFilter('municipios')"
    />

    <!-- Decretacoes Vigentes -->
    <StatCardWithBreakdown
      title="Decretacoes Vigentes"
      :value="statistics.decretacoesVigentes"
      :ecp="statistics.decretacoesVigentesEcp"
      :se="statistics.decretacoesVigentesSe"
      :n1="statistics.decretacoesVigentesN1"
      variant="success"
      :icon="CheckCircleIcon"
      @click="handleFilter('vigentes')"
    />
  </div>
</template>

<script setup>
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
      registros: 0,
      registrosEcp: 0,
      registrosSe: 0,
      registrosN1: 0,
      decretacoes: 0,
      decretacoesEcp: 0,
      decretacoesSe: 0,
      decretacoesN1: 0,
      municipiosAtingidos: 0,
      municipiosAtingidosEcp: 0,
      municipiosAtingidosSe: 0,
      municipiosAtingidosN1: 0,
      decretacoesVigentes: 0,
      decretacoesVigentesEcp: 0,
      decretacoesVigentesSe: 0,
      decretacoesVigentesN1: 0,
    }),
  },
});

const emit = defineEmits(['filter']);

function handleFilter(type) {
  emit('filter', type);
}
</script>
