<template>
  <StatCardsGrid>
    <StatCard
      title="Total"
      :value="statistics.total || 0"
      variant="info"
      :icon="UsersGroupIcon"
      clickable
      @click="$emit('filter', {})"
    />
    <StatCard
      title="Ativos"
      :value="statistics.ativos || 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="Beneficiários ativos"
      clickable
      @click="$emit('filter', { status: 'ativo' })"
    />
    <StatCard
      title="Em Abrigo"
      :value="statistics.em_abrigo || 0"
      variant="info"
      :icon="HomeModernIcon"
      subtitle="Abrigados"
      clickable
      @click="$emit('filter', { abrigo_id: 'not_null' })"
    />
    <StatCard
      title="Fora de Abrigo"
      :value="statistics.fora_de_abrigo || 0"
      variant="warning"
      :icon="UsersIcon"
      subtitle="Não abrigados"
      clickable
      @click="$emit('filter', { abrigo_id: 'null' })"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import UsersGroupIcon from '@/Components/Icons/UsersGroupIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import HomeModernIcon from '@/Components/Icons/HomeModernIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';

defineProps({
  statistics: {
    type: Object,
    required: true,
  },
});

defineEmits(['filter']);
</script>
