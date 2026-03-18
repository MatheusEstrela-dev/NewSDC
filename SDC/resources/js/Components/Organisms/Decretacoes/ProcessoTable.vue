<template>
  <div class="bg-white dark:bg-slate-800/50 rounded-lg sm:rounded-xl shadow-lg border border-slate-200 dark:border-slate-700/50 overflow-hidden">
    <!-- Header -->
    <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 border-b border-slate-200 dark:border-slate-700/50 flex justify-between items-center bg-slate-50 dark:bg-slate-800/70">
      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base truncate">
          <DocumentIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary-400 flex-shrink-0" />
          <span class="truncate">{{ title }}</span>
        </h3>
        <p class="text-xs text-slate-400 mt-0.5 hidden sm:block">{{ subtitle }}</p>
      </div>
      <span class="bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded border border-slate-200 dark:border-slate-600/50 flex-shrink-0 ml-2">
        {{ processos.length }}
      </span>
    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto -mx-px">
      <table class="w-full text-sm">
        <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/50">
          <tr>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Data</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Tipo</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden sm:table-cell">Desastre</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden md:table-cell">Analista</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Reconhecimento</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Nº Protocolo S2ID</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden lg:table-cell">Vigência</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right whitespace-nowrap w-36 min-w-36">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/30">
          <tr
            v-for="processo in processos"
            :key="processo.id"
            class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors group"
          >
            <!-- Protocolo -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <div class="font-medium text-slate-800 dark:text-slate-200 text-xs sm:text-sm whitespace-nowrap">{{ formatDate(processo.data_entrada) }}</div>
              <div class="text-xs text-slate-500 whitespace-nowrap">{{ processo.n_protocolo_fide }}</div>
            </td>

            <!-- Tipo -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <TipoProcessoBadge :tipo="processo.processo" />
            </td>

            <!-- Desastre -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 hidden sm:table-cell">
              <div class="text-slate-700 dark:text-slate-300 font-medium text-xs sm:text-sm max-w-[150px] truncate">{{ processo.tipo_desastre_nome || '—' }}</div>
              <div v-if="processo.tipo_desastre_cobrade" class="text-xs text-slate-500">
                COBRADE: {{ processo.tipo_desastre_cobrade }}
              </div>
            </td>

            <!-- Analista -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-slate-600 dark:text-slate-300 text-xs sm:text-sm hidden md:table-cell max-w-[120px] truncate">
              {{ processo.analista || '—' }}
            </td>

            <!-- Reconhecimento -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <StatusBadge v-if="processo.reconhecimento" :status="processo.reconhecimento" />
              <span v-else class="text-slate-400 text-xs">—</span>
            </td>

            <!-- Nº Protocolo S2ID -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-slate-600 dark:text-slate-300 text-xs sm:text-sm">
              {{ processo.n_protocolo_fide || '—' }}
            </td>

            <!-- Vigência -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 hidden lg:table-cell">
              <PrazoBadge
                :dias-restantes="processo.dias_restantes"
                :data-vencimento="processo.data_vencimento"
              />
            </td>

            <!-- Acoes -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 w-36 min-w-36">
              <div class="flex items-center justify-end">
                <TableActions
                  :show-view="true"
                  :show-print="true"
                  :show-edit="canEdit"
                  :show-attachments="false"
                  :show-delete="canDelete"
                  :show-warning="true"
                  :show-options="true"
                  @view="openDetailModal(processo)"
                  @print="$emit('print', processo.id)"
                  @edit="openEditChoiceModal(processo.id)"
                  @delete="$emit('delete', processo.id)"
                  @warning="$emit('warning', processo.id)"
                  @options="$emit('options', processo.id)"
                />
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="processos.length === 0">
            <td colspan="7" class="px-6 py-12 text-center">
              <DocumentIcon class="w-12 h-12 text-slate-600 mx-auto mb-3" />
              <p class="text-slate-400 font-medium">Nenhum processo encontrado</p>
              <p class="text-slate-500 text-sm mt-1">Tente ajustar os filtros de busca</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal de Detalhes -->
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
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import DocumentIcon from '../../Icons/DocumentTextIcon.vue';
import PrazoBadge from '../../Molecules/Decretacoes/PrazoBadge.vue';
import StatusBadge from '../../Molecules/Decretacoes/StatusBadge.vue';
import TipoProcessoBadge from '../../Molecules/Decretacoes/TipoProcessoBadge.vue';
import TableActions from '../../Molecules/Table/TableActions.vue';
import Pagination from '../../Molecules/Navigation/Pagination.vue';
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
