<template>
  <StatCardsGrid>
    <StatCard
      title="Disponíveis"
      :value="statistics.disponiveis || 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="Aparecem na lista de pedido"
      clickable
      @click="$emit('filter', { situacao: 'disponivel' })"
    />
    <StatCard
      title="Indisponíveis"
      :value="statistics.indisponiveis || 0"
      variant="warning"
      :icon="XMarkIcon"
      subtitle="Fora da lista de pedido"
      clickable
      @click="$emit('filter', { situacao: 'indisponivel' })"
    />
    <StatCard
      title="Com saldo"
      :value="statistics.com_saldo || 0"
      variant="info"
      :icon="ArchiveBoxIcon"
      subtitle="Existem em algum depósito"
      clickable
      @click="$emit('filter', { situacao: 'com_saldo' })"
    />
    <StatCard
      title="Ofertados sem estoque"
      :value="statistics.sem_saldo || 0"
      variant="danger"
      :icon="ExclamationTriangleIcon"
      subtitle="Podem ser pedidos, mas não há saldo"
      clickable
      @click="$emit('filter', { situacao: 'sem_saldo' })"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

/**
 * O total nao vira cartao porque o ListContainer ja exibe a contagem do
 * catalogo no proprio cabecalho da lista.
 *
 * "Com saldo" e "Ofertados sem estoque" nao sao atributos do material, e sim
 * recortes calculados sobre a projecao de saldo. O segundo e o unico cartao
 * acionavel: material que o municipio pode pedir e o CEDEC nao tem.
 */
defineProps({
  statistics: { type: Object, required: true },
});

defineEmits(['filter']);
</script>
