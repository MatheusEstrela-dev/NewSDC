<template>
  <StatCardsGrid>
    <StatCard
      title="Total"
      :value="statistics.total || 0"
      variant="info"
      :icon="ClipboardDocumentListIcon"
      subtitle="Liberações no período"
      clickable
      @click="$emit('filter', { status: '' })"
    />
    <StatCard
      title="Pendentes"
      :value="statistics.pendentes || 0"
      variant="warning"
      :icon="ClockIcon"
      subtitle="Sem recibo de retirada"
      clickable
      @click="$emit('filter', { status: 0 })"
    />
    <StatCard
      title="Pagas"
      :value="statistics.pagas || 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="Material retirado"
      clickable
      @click="$emit('filter', { status: 1 })"
    />
    <StatCard
      title="Canceladas"
      :value="statistics.canceladas || 0"
      variant="danger"
      :icon="XMarkIcon"
      subtitle="Com motivo registrado"
      clickable
      @click="$emit('filter', { status: 2 })"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

/**
 * Cada cartao e um recorte de status, entao todos filtram. O controller conta
 * sem aplicar o proprio status: clicar em Canceladas nao zera os outros tres.
 */
defineProps({
  statistics: {
    type: Object,
    required: true,
  },
});

defineEmits(['filter']);
</script>
