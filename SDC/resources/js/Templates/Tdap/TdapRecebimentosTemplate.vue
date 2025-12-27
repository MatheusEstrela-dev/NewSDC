<template>
  <div class="tdap-recebimentos-container">
    <TdapRecebimentosPageHeader />

    <CardBase variant="default" padding="lg" class="bg-slate-800/60 border-slate-700/50">
      <Heading :level="4" color="white" class="mb-4">Lista de Recebimentos</Heading>

      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-slate-700">
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Número</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">NF</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Placa</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Data Chegada</Text>
              </th>
              <th class="px-4 py-3 text-left">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Status</Text>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr v-for="recebimento in recebimentos" :key="recebimento.id" class="hover:bg-slate-700/30 transition-colors">
              <td class="px-4 py-4">
                <Text size="sm" color="white" weight="medium">{{ recebimento.numero_recebimento }}</Text>
              </td>
              <td class="px-4 py-4">
                <Text size="sm" color="muted">{{ recebimento.nota_fiscal }}</Text>
              </td>
              <td class="px-4 py-4">
                <Text size="sm" color="muted">{{ recebimento.placa_veiculo }}</Text>
              </td>
              <td class="px-4 py-4">
                <Text size="sm" color="muted">{{ formatDate(recebimento.data_chegada) }}</Text>
              </td>
              <td class="px-4 py-4">
                <RecebimentoStatusBadge :status="recebimento.status" />
              </td>
            </tr>
            <tr v-if="!recebimentos || recebimentos.length === 0">
              <td colspan="5" class="px-4 py-8 text-center">
                <Text size="sm" color="muted">Nenhum recebimento cadastrado</Text>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardBase>
  </div>
</template>

<script setup>
import TdapRecebimentosPageHeader from '@/Components/Organisms/Tdap/Header/TdapRecebimentosPageHeader.vue';
import RecebimentoStatusBadge from '@/Components/Atoms/Tdap/RecebimentoStatusBadge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

const props = defineProps({
  recebimentos: {
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
  return new Date(date).toLocaleDateString('pt-BR');
};
</script>

<style scoped>
.tdap-recebimentos-container {
  @apply w-full min-h-screen;
  padding: 1.5rem;
  background: #0f172a;
}

@media (min-width: 640px) {
  .tdap-recebimentos-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .tdap-recebimentos-container {
    padding: 2rem 2.5rem;
  }
}

@media (min-width: 1280px) {
  .tdap-recebimentos-container {
    padding: 2rem 3rem;
  }
}
</style>
