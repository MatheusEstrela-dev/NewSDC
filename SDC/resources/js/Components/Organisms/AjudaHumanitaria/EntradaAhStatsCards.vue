<template>
  <StatCardsGrid>
    <StatCard
      title="Total"
      :value="statistics.total || 0"
      variant="info"
      :icon="UploadIcon"
      subtitle="Entradas no período"
      clickable
      @click="$emit('filter', { situacao: '' })"
    />
    <StatCard
      title="Ativas"
      :value="statistics.ativas || 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="Recebimentos válidos"
      clickable
      @click="$emit('filter', { situacao: 'ativa' })"
    />
    <StatCard
      title="Correções de saldo"
      :value="statistics.correcoes || 0"
      variant="warning"
      :icon="ExclamationTriangleIcon"
      subtitle="Lançadas com quantidade negativa"
      clickable
      @click="$emit('filter', { situacao: 'correcao' })"
    />
    <StatCard
      title="Canceladas"
      :value="statistics.canceladas || 0"
      variant="danger"
      :icon="XMarkIcon"
      subtitle="Sem efeito no estoque"
      clickable
      @click="$emit('filter', { situacao: 'cancelada' })"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import UploadIcon from '@/Components/Icons/UploadIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

/**
 * Correcao nao e uma situacao do registro, e sim um recorte: entrada que tem
 * item de quantidade negativa. O legado lancava baixa assim, em vez de criar um
 * tipo proprio de movimento, e por isso ela aparece ao lado das demais.
 */
defineProps({
  statistics: { type: Object, required: true },
});

defineEmits(['filter']);
</script>
