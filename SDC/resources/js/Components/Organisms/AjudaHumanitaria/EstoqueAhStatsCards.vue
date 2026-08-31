<template>
  <StatCardsGrid>
    <StatCard
      title="Itens com saldo"
      :value="statistics.itens_com_saldo || 0"
      variant="info"
      :icon="ArchiveBoxIcon"
      subtitle="Todo o estoque"
      clickable
      @click="$emit('filter', { nivel: '' })"
    />
    <StatCard
      title="Saldo crítico"
      :value="statistics.nivel_critico || 0"
      variant="danger"
      :icon="ExclamationTriangleIcon"
      subtitle="Menos de 50 unidades"
      clickable
      @click="$emit('filter', { nivel: 'critico' })"
    />
    <StatCard
      title="Saldo baixo"
      :value="statistics.nivel_baixo || 0"
      variant="warning"
      :icon="BoltIcon"
      subtitle="De 50 a 199 unidades"
      clickable
      @click="$emit('filter', { nivel: 'baixo' })"
    />
    <StatCard
      title="Saldo confortável"
      :value="statistics.nivel_confortavel || 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="200 unidades ou mais"
      clickable
      @click="$emit('filter', { nivel: 'confortavel' })"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

/**
 * Cartoes de filtro rapido por nivel de estoque.
 *
 * Cada cartao corresponde a um recorte real das linhas listadas, e por isso
 * pode filtrar. A versao anterior mostrava agregados (materiais distintos,
 * depositos, soma) que nao sao subconjunto de linha alguma: clicar neles nao
 * teria o que filtrar.
 */
defineProps({
  statistics: {
    type: Object,
    required: true,
  },
});

defineEmits(['filter']);
</script>
