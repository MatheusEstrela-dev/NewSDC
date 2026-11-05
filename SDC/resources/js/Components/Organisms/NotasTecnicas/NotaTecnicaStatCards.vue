<template>
  <!--
    Cards = filtros rapidos (padrao do projeto, ver Organisms/Pmda/PmdaStatisticsCards.vue).
    Organismo que nao navega: emite `filter` e a pagina traduz em visita.
    espaco-inferior=false porque a raiz da pagina usa space-y-6.
  -->
  <StatCardsGrid :colunas="estatisticas.rascunhos === null ? 3 : 4" :espaco-inferior="false">
    <StatCard
      title="Em vigor"
      :value="estatisticas.total"
      subtitle="Ver todas as notas"
      variant="info"
      :icon="DocumentTextIcon"
      clickable
      @click="$emit('filter', null)"
    />

    <StatCard
      title="Não lidas"
      :value="estatisticas.nao_lidas"
      subtitle="Versões que você ainda não leu"
      variant="warning"
      :icon="EyeIcon"
      clickable
      @click="$emit('filter', 'nao_lidas')"
    />

    <StatCard
      title="Revogadas"
      :value="estatisticas.revogadas"
      subtitle="Notas obsoletas"
      variant="danger"
      :icon="ArchiveBoxIcon"
      clickable
      @click="$emit('filter', 'revogada')"
    />

    <StatCard
      v-if="estatisticas.rascunhos !== null"
      title="Rascunhos"
      :value="estatisticas.rascunhos"
      subtitle="Ainda não publicadas"
      variant="success"
      :icon="PencilSquareIcon"
      clickable
      @click="$emit('filter', 'rascunho')"
    />
  </StatCardsGrid>
</template>

<script setup>
import { ArchiveBoxIcon, DocumentTextIcon, EyeIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';

defineProps({
  estatisticas: {
    type: Object,
    default: () => ({ total: 0, nao_lidas: 0, revogadas: 0, rascunhos: null }),
  },
});

defineEmits(['filter']);
</script>
