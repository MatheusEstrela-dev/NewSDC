<template>
  <ListContainer
    :title="title"
    :icon="DocumentIcon"
    :subtitle="subtitle"
    :count="total ?? processos.length"
  >
    <table class="w-full text-sm text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap">Data</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap">Tipo</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap hidden sm:table-cell">Desastre</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap hidden md:table-cell">Analista</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap">Reconhecimento</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap">Nº Protocolo S2ID</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap hidden lg:table-cell">Vigência</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right whitespace-nowrap w-36 min-w-36">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr
            v-for="processo in processos"
            :key="processo.id"
            class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group"
          >
            <!-- Protocolo -->
            <td class="px-4 py-3">
              <div class="font-medium text-slate-800 dark:text-slate-200 text-xs sm:text-sm whitespace-nowrap">{{ formatDate(processo.data_entrada) }}</div>
              <div class="text-xs text-slate-500 whitespace-nowrap">{{ processo.n_protocolo_fide }}</div>
            </td>

            <!-- Tipo -->
            <td class="px-4 py-3">
              <TipoProcessoBadge :tipo="processo.processo" />
            </td>

            <!-- Desastre. O COBRADE abre a classificacao completa no hover,
                 usando tipo_desastre_completo que ja vem no ProcessoResource. -->
            <td class="px-4 py-3 hidden sm:table-cell">
              <div class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm max-w-[150px] truncate">{{ processo.tipo_desastre_nome || '—' }}</div>
              <CobradeHoverCard
                v-if="processo.tipo_desastre_cobrade"
                :desastre="processo.tipo_desastre_completo"
                trigger-class="text-xs text-slate-500 underline"
              >
                COBRADE: {{ processo.tipo_desastre_cobrade }}
              </CobradeHoverCard>
            </td>

            <!-- Analista -->
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs sm:text-sm hidden md:table-cell max-w-[120px] truncate">
              {{ processo.analista || '—' }}
            </td>

            <!-- Reconhecimento -->
            <td class="px-4 py-3">
              <StatusBadge v-if="processo.reconhecimento" :status="processo.reconhecimento" />
              <span v-else class="text-slate-400 text-xs">—</span>
            </td>

            <!-- Nº Protocolo S2ID -->
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs sm:text-sm">
              {{ processo.n_protocolo_fide || '—' }}
            </td>

            <!-- Vigência -->
            <td class="px-4 py-3 hidden lg:table-cell">
              <PrazoBadge
                :dias-restantes="processo.dias_restantes"
                :data-vencimento="processo.data_vencimento"
              />
            </td>

            <!-- Acoes -->
            <td class="px-4 py-3 w-36 min-w-36">
              <div class="flex items-center justify-end">
                <ActionButton
                  module="decretacoes"
                  resource="processos"
                  :actions="[
                    { action: 'view',    handler: () => openDetailModal(processo) },
                    { action: 'print',   handler: () => emit('print', processo.id) },
                    { action: 'edit',    handler: () => openEditChoiceModal(processo.id), allowed: canEdit },
                    { action: 'delete',  handler: () => emit('delete', processo.id),  allowed: canDelete },
                    { action: 'warning', handler: () => emit('warning', processo.id) },
                  ]"
                />
              </div>
            </td>
          </tr>

          <tr v-if="processos.length === 0">
            <td colspan="8" class="p-0">
              <ListEmptyState
                :icon="DocumentIcon"
                title="Nenhum processo encontrado"
              />
            </td>
          </tr>
        </tbody>
      </table>

    <DecretacaoDetailModal
      :show="showDetailModal"
      :processo="selectedProcesso"
      :loading="loadingDetail"
      @close="closeDetailModal"
      @generate-report="handleGenerateReport"
    />

    <!-- Modal de Escolha de Edicao -->
    <EditChoiceModal
      :show="showEditChoiceModal"
      :processo-id="selectedProcessoIdForEdit"
      @close="closeEditChoiceModal"
    />
  </ListContainer>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import DocumentIcon from '../../Icons/DocumentTextIcon.vue';
import CobradeHoverCard from '../../Molecules/Decretacoes/CobradeHoverCard.vue';
import PrazoBadge from '../../Molecules/Decretacoes/PrazoBadge.vue';
import StatusBadge from '../../Molecules/Decretacoes/StatusBadge.vue';
import TipoProcessoBadge from '../../Molecules/Decretacoes/TipoProcessoBadge.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '../../Molecules/Navigation/Pagination.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import DecretacaoDetailModal from './Details/DecretacaoDetailModal.vue';
import EditChoiceModal from './EditChoiceModal.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Processos de Decretacao',
  },
  subtitle: {
    type: String,
    default: 'Reconhecimentos de desastre',
  },
  processos: {
    type: Array,
    default: () => [],
  },
  total: {
    type: Number,
    default: null,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['view', 'print', 'edit', 'generate-report', 'warning', 'options', 'delete']);

const showDetailModal = ref(false);
const selectedProcesso = ref(null);
const loadingDetail = ref(false);
const showEditChoiceModal = ref(false);
const selectedProcessoIdForEdit = ref(null);

const openDetailModal = async (processo) => {
  selectedProcesso.value = processo;
  showDetailModal.value = true;
  loadingDetail.value = true;

  try {
    const response = await axios.get(`/api/v1/decretacoes/${processo.id}`, { withCredentials: true });
    console.log('API Response:', response.data);
    if (response.data.success && response.data.data) {
      console.log('Totais:', response.data.data.totais);
      selectedProcesso.value = response.data.data;
    }
  } catch (error) {
    console.error('Erro ao carregar detalhes do processo:', error);
  } finally {
    loadingDetail.value = false;
  }
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedProcesso.value = null;
};

const handleGenerateReport = (processo) => {
  emit('generate-report', processo);
  closeDetailModal();
};

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('pt-BR');
};

const openEditChoiceModal = (id) => {
  selectedProcessoIdForEdit.value = id;
  showEditChoiceModal.value = true;
};

const closeEditChoiceModal = () => {
  showEditChoiceModal.value = false;
  selectedProcessoIdForEdit.value = null;
};
</script>
