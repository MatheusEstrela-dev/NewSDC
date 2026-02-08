<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import RecebimentoStatusBadge from '@/Components/Atoms/Tdap/RecebimentoStatusBadge.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import PrinterIcon from '@/Components/Icons/PrinterIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import TdapRecebimentoCard from '@/Components/Molecules/Tdap/TdapRecebimentoCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import PrintTdapRecebimentoModal from '@/Components/Organisms/Tdap/Print/PrintTdapRecebimentoModal.vue';
import { useMobile } from '@/composables/useMobile';
import { ref } from 'vue';

// Detecção mobile
const { isMobile } = useMobile();

const props = defineProps({
  recebimentos: {
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
  return new Date(date).toLocaleDateString('pt-BR');
};

// Print Logic
const showPrintModal = ref(false);
const selectedRecebimento = ref(null);

const handlePrint = (recebimento) => {
  selectedRecebimento.value = recebimento;
  showPrintModal.value = true;
};
</script>

<template>
  <div class="tdap-recebimentos-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Recebimentos TDAP"
      description="Gestão de recebimentos de produtos"
      :icon="TruckIcon"
      variant="gradient"
    />

    <!-- Desktop: Tabela -->
    <CardBase v-if="!isMobile" variant="default" padding="lg" class="bg-slate-800/60 border-slate-700/50">
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
              <th class="px-4 py-3 text-center">
                <Text size="xs" color="muted" weight="medium" class="uppercase">Ações</Text>
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
              <td class="px-4 py-4 text-center">
                <button
                  @click="handlePrint(recebimento)"
                  class="text-slate-400 hover:text-white transition-colors p-1"
                  title="Imprimir Recebimento"
                >
                  <PrinterIcon class="w-5 h-5" />
                </button>
              </td>
            </tr>
            <tr v-if="!recebimentos || recebimentos.length === 0">
              <td colspan="6" class="px-4 py-8 text-center">
                <Text size="sm" color="muted">Nenhum recebimento cadastrado</Text>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardBase>

    <!-- Mobile: Cards -->
    <div v-else class="grid grid-cols-1 gap-4">
      <div v-for="recebimento in recebimentos" :key="recebimento.id" class="relative">
         <TdapRecebimentoCard
          :recebimento="recebimento"
        />
        <button
            @click="handlePrint(recebimento)"
            class="absolute top-4 right-4 text-slate-400 hover:text-white bg-slate-800/80 p-1 rounded"
            title="Imprimir"
        >
            <PrinterIcon class="w-5 h-5" />
        </button>
      </div>

      <div v-if="!recebimentos || recebimentos.length === 0" class="text-center py-8 bg-slate-800/60 border border-slate-700/50 rounded-lg">
        <Text size="sm" color="muted">Nenhum recebimento cadastrado</Text>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="(page) => $emit('page-change', page)"
      />
    </div>

    <!-- Modals -->
    <PrintTdapRecebimentoModal
      :show="showPrintModal"
      :recebimento="selectedRecebimento"
      @close="showPrintModal = false"
    />
  </div>
</template>

<style scoped>
.tdap-recebimentos-container {
  @apply w-full pb-8;
  background: #0f172a;
}
</style>