<template>
  <StatCardsGrid>
    <StatCard
      title="Lançamentos"
      :value="statistics.lancamentos || 0"
      variant="info"
      :icon="ClipboardDocumentListIcon"
      subtitle="No recorte atual"
      clickable
      @click="$emit('filter', { sentido: '' })"
    />
    <StatCard
      title="Entradas"
      :value="statistics.entradas || 0"
      variant="success"
      :icon="UploadIcon"
      subtitle="Somaram ao estoque"
      clickable
      @click="$emit('filter', { sentido: 'entrada' })"
    />
    <StatCard
      title="Saídas"
      :value="statistics.saidas || 0"
      variant="warning"
      :icon="ArrowsRightLeftIcon"
      subtitle="Baixaram do estoque"
      clickable
      @click="$emit('filter', { sentido: 'saida' })"
    />
    <StatCard
      title="Efeito líquido"
      :value="saldoLiquido"
      :variant="(statistics.saldo_liquido || 0) < 0 ? 'danger' : 'info'"
      :icon="ArchiveBoxIcon"
      subtitle="Soma com sinal do recorte"
    />
  </StatCardsGrid>
</template>

<script setup>
import { computed } from 'vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import UploadIcon from '@/Components/Icons/UploadIcon.vue';

/**
 * Entrada e saida saem do SINAL da quantidade, nao do tipo do lancamento: um
 * estorno de entrada e negativo, e contar pelo nome do tipo o colocaria do
 * lado errado.
 *
 * "Efeito liquido" nao e clicavel porque nao e um recorte, e o resultado da
 * soma dos que estao acima.
 */
const props = defineProps({
  statistics: { type: Object, required: true },
});

defineEmits(['filter']);

const numero = new Intl.NumberFormat('pt-BR');

const saldoLiquido = computed(() => numero.format(props.statistics.saldo_liquido || 0));
</script>
