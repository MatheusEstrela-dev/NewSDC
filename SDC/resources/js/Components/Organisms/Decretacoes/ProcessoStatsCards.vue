<template>
  <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 md:gap-4 mb-4 md:mb-6">
    <div @click="$emit('filter', 'all')" class="cursor-pointer">
      <StatCard
        title="Total de Processos"
        :value="statistics.total || 0"
        variant="info"
        :icon="DocumentTextIcon"
      />
    </div>
    <div @click="$emit('filter', 'vigente')" class="cursor-pointer">
      <StatCard
        title="Vigentes"
        :value="statistics.vigentes || 0"
        variant="success"
        :icon="CheckCircleIcon"
        subtitle="Processos ativos"
      />
    </div>
    <div @click="$emit('filter', 'vencido')" class="cursor-pointer">
      <StatCard
        title="Vencidos"
        :value="statistics.vencidos || 0"
        variant="danger"
        :icon="ExclamationTriangleIcon"
        subtitle="Prazo expirado"
      />
    </div>
    <div @click="$emit('filter', 'proximo_vencer')" class="cursor-pointer">
      <StatCard
        title="Próximos ao Vencimento"
        :value="statistics.proximos_vencer || 0"
        variant="warning"
        :icon="ClockIcon"
        subtitle="≤ 15 dias restantes"
      />
    </div>
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';

defineProps({
  statistics: {
    type: Object,
    required: true,
  },
});

defineEmits(['filter']);
</script>
