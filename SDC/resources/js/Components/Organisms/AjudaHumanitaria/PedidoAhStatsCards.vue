<template>
  <StatCardsGrid :colunas="5">
    <StatCard
      title="Total"
      :value="estatisticas.total || 0"
      variant="info"
      :icon="DocumentTextIcon"
      clickable
      @click="$emit('filtrar', {})"
    />
    <StatCard
      title="Em edição"
      :value="estatisticas.em_edicao || 0"
      variant="warning"
      :icon="PencilIcon"
      subtitle="Com o município"
      clickable
      @click="$emit('filtrar', { status: 0 })"
    />
    <StatCard
      title="Em análise"
      :value="estatisticas.em_analise || 0"
      variant="info"
      :icon="ClipboardIcon"
      subtitle="DLOG e Diretor"
      clickable
      @click="$emit('filtrar', { status: 1 })"
    />
    <StatCard
      title="Em atendimento"
      :value="estatisticas.em_atendimento || 0"
      variant="info"
      :icon="TruckIcon"
      subtitle="Aprovado até retirada"
      clickable
      @click="$emit('filtrar', { status: 5 })"
    />
    <StatCard
      title="Finalizados"
      :value="estatisticas.finalizados || 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="Prestação homologada"
      clickable
      @click="$emit('filtrar', { status: 9 })"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineProps({
  estatisticas: { type: Object, default: () => ({}) },
});

defineEmits(['filtrar']);
</script>
