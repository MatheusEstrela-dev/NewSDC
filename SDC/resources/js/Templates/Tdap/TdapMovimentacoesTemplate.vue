<template>
  <div class="tdap-movimentacoes-container">
    <TdapMovimentacoesPageHeader />

    <CardBase variant="default" padding="lg" class="bg-slate-800/60 border-slate-700/50">
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
  </div>
</template>

<script setup>
import TdapMovimentacoesPageHeader from '@/Components/Organisms/Tdap/Header/TdapMovimentacoesPageHeader.vue';
import MovimentacaoTypeBadge from '@/Components/Atoms/Tdap/MovimentacaoTypeBadge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

const props = defineProps({
  movimentacoes: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    default: () => ({}),
  },
});

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
  @apply w-full min-h-screen;
  padding: 1.5rem;
  background: #0f172a;
}

@media (min-width: 640px) {
  .tdap-movimentacoes-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .tdap-movimentacoes-container {
    padding: 2rem 2.5rem;
  }
}

@media (min-width: 1280px) {
  .tdap-movimentacoes-container {
    padding: 2rem 3rem;
  }
}
</style>
