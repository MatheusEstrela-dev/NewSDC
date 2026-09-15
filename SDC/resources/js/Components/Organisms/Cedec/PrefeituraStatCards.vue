<template>
  <!--
    Organismo, nao molecula: os cinco equivalentes do projeto -- PmdaStatisticsCards,
    CisternaStatisticsCards, Demandas/Statistics, Rat/Statistics, Tdap/Statistics --
    vivem todos em Organisms/. Molecula aqui e o StatCardsGrid/StatCard que ele
    compoe. Ainda assim nao navega, nao le estado global e nao conhece rota: quem
    traduz `filter` em visita ao servidor e a pagina.

    espaco-inferior=false porque a raiz da pagina usa space-y-6. Com a mb-6 propria
    do grid somando ao space-y-6 do pai, o primeiro intervalo ficaria em 48px contra
    os 24px de todos os outros.
  -->
  <StatCardsGrid :espaco-inferior="false">
    <!--
      O card de total TAMBEM e clickable, seguindo Organisms/Pmda/PmdaStatisticsCards.vue:
      limpar filtro E um filtro. Aqui emite null, que e o que PrefeituraFiltroDTO
      aceita em $pendencia, junto com as tres strings.
    -->
    <StatCard
      title="Municípios"
      :value="estatisticas.total"
      subtitle="Ver todos os municípios"
      variant="info"
      :icon="BuildingOffice2Icon"
      clickable
      @click="$emit('filter', null)"
    />

    <StatCard
      title="Sem e-mail"
      :value="estatisticas.sem_email"
      subtitle="Filtrar pendência"
      variant="danger"
      :icon="EnvelopeIcon"
      clickable
      @click="$emit('filter', 'sem_email')"
    />

    <StatCard
      title="Sem telefone"
      :value="estatisticas.sem_telefone"
      subtitle="Filtrar pendência"
      variant="warning"
      :icon="PhoneIcon"
      clickable
      @click="$emit('filter', 'sem_telefone')"
    />

    <StatCard
      title="Sem foto"
      :value="estatisticas.sem_foto"
      subtitle="Filtrar pendência"
      variant="warning"
      :icon="PhotoIcon"
      clickable
      @click="$emit('filter', 'sem_foto')"
    />
  </StatCardsGrid>
</template>

<script setup>
import { BuildingOffice2Icon, EnvelopeIcon, PhoneIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

defineProps({
  estatisticas: {
    type: Object,
    default: () => ({ total: 0, sem_email: 0, sem_telefone: 0, sem_foto: 0 }),
  },
});

/** Payload: 'sem_email' | 'sem_telefone' | 'sem_foto' | null (null limpa a pendência). */
defineEmits(['filter']);
</script>
