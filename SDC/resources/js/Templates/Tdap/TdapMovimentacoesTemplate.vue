<template>
  <div class="tdap-movimentacoes-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Movimentações TDAP"
      description="Controle de movimentações de estoque"
      :icon="ArrowsRightLeftIcon"
      variant="gradient"
    />

    <!-- Desktop: Tabela -->
    <CardBase v-if="!isMobile" variant="default" padding="lg" class="bg-slate-800/60 border-slate-700/50">
      <Heading :level="4" color="white" class="mb-4">Movimentações de Estoque</Heading>

      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-slate-700">
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Número</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Tipo</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Produto</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Quantidade</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Data</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Origem/Destino</Text>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr v-for="mov in movimentacoes" :key="mov.id" class="hover:bg-slate-700/30 transition-colors">
              <td class="px-4 py-4">
                <Text size="sm" color="white" weight="medium">{{ mov.numero_movimentacao }}</Text>
              </td>
              <td class="px-4 py-4">
                <MovimentacaoTypeBadge :type="mov.tipo" />
              </td>
              <td class="px-4 py-4">
                <Text size="sm" color="muted">{{ mov.product?.nome || '-' }}</Text>
              </td>
              <td class="px-4 py-4">
                <Text size="sm" color="white" weight="medium">{{ mov.quantidade }}</Text>
              </td>
              <td class="px-4 py-4">
                <Text size="sm" color="muted">{{ formatDate(mov.data_movimentacao) }}</Text>
              </td>
              <td class="px-4 py-4">
                <div>
                  <Text size="sm" color="muted">{{ mov.origem || '-' }}</Text>
                  <Text size="xs" color="muted" class="opacity-60">→ {{ mov.destino || '-' }}</Text>
                </div>
              </td>
            </tr>
            <tr v-if="!movimentacoes || movimentacoes.length === 0">
              <td colspan="6" class="px-4 py-8 text-center">
                <Text size="sm" color="muted">Nenhuma movimentação registrada</Text>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardBase>

    <!-- Mobile: Cards -->
    <div v-else class="grid grid-cols-1 gap-4">
      <TdapMovimentacaoCard
        v-for="mov in movimentacoes"
        :key="mov.id"
        :movimentacao="mov"
      />
      <div v-if="!movimentacoes || movimentacoes.length === 0" class="text-center py-8 bg-slate-800/60 border border-slate-700/50 rounded-lg">
        <Text size="sm" color="muted">Nenhuma movimentação registrada</Text>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="(page) => $emit('page-change', page)"
      />
    </div>
  </div>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import MovimentacaoTypeBadge from '@/Components/Atoms/Tdap/MovimentacaoTypeBadge.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import TdapMovimentacaoCard from '@/Components/Molecules/Tdap/TdapMovimentacaoCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { useMobile } from '@/Composables/useMobile';

// Detecção mobile
const { isMobile } = useMobile();

const props = defineProps({
  movimentacoes: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({}),
  },
  pagination: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['page-change']);

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('pt-BR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<style scoped>
.tdap-movimentacoes-container {
  @apply w-full pb-8;
  background: #0f172a;
}
</style>
