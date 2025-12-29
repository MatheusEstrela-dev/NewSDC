<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div @click="$emit('filter', 'all')" class="cursor-pointer">
      <StatCard
        title="Total"
        :value="statistics.total || 0"
        variant="info"
        :icon="UsersGroupIcon"
      />
    </div>
    <div @click="$emit('filter', 'ativos')" class="cursor-pointer">
      <StatCard
        title="Ativos"
        :value="statistics.ativos || 0"
        variant="success"
        :icon="CheckCircleIcon"
        subtitle="Beneficiários ativos"
      />
    </div>
    <div @click="$emit('filter', 'em_abrigo')" class="cursor-pointer">
      <StatCard
        title="Em Abrigo"
        :value="statistics.em_abrigo || 0"
        variant="info"
        :icon="HomeModernIcon"
        subtitle="Abrigados"
      />
    </div>
    <div @click="$emit('filter', 'fora_abrigo')" class="cursor-pointer">
      <StatCard
        title="Fora de Abrigo"
        :value="statistics.fora_de_abrigo || 0"
        variant="warning"
        :icon="UsersIcon"
        subtitle="Não abrigados"
      />
    </div>
  </div>
</template>

<script setup>
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
