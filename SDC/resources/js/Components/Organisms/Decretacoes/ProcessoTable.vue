<template>
  <div class="bg-slate-800/50 rounded-lg sm:rounded-xl shadow-lg border border-slate-700/50 overflow-hidden">
    <!-- Header -->
    <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 border-b border-slate-700/50 flex justify-between items-center bg-slate-800/70">
      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-slate-100 flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base truncate">
          <DocumentIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary-400 flex-shrink-0" />
          <span class="truncate">{{ title }}</span>
        </h3>
        <p class="text-xs text-slate-400 mt-0.5 hidden sm:block">{{ subtitle }}</p>
      </div>
      <span class="bg-slate-700/50 text-slate-300 text-xs font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded border border-slate-600/50 flex-shrink-0 ml-2">
        {{ processos.length }}
      </span>
    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto -mx-px">
      <table class="w-full text-sm">
        <thead class="text-xs text-slate-400 uppercase font-semibold bg-slate-800/80 border-b border-slate-700/50">
          <tr>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Protocolo</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Tipo</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden sm:table-cell">Desastre</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden md:table-cell">Analista</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap">Status</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left whitespace-nowrap hidden lg:table-cell">Vigência</th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right whitespace-nowrap w-36 min-w-36">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/30">
          <tr
            v-for="processo in processos"
            :key="processo.id"
            class="hover:bg-slate-700/20 transition-colors group"
          >
            <!-- Protocolo -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <div class="font-medium text-slate-200 text-xs sm:text-sm whitespace-nowrap">{{ processo.n_protocolo_fide }}</div>
              <div class="text-xs text-slate-500 whitespace-nowrap">{{ formatDate(processo.data_entrada) }}</div>
            </td>

            <!-- Tipo -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <TipoProcessoBadge :tipo="processo.processo" />
            </td>

            <!-- Desastre -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 hidden sm:table-cell">
              <div class="text-slate-300 font-medium text-xs sm:text-sm max-w-[150px] truncate">{{ processo.tipo_desastre_nome || '—' }}</div>
              <div v-if="processo.tipo_desastre_cobrade" class="text-xs text-slate-500">
                COBRADE: {{ processo.tipo_desastre_cobrade }}
              </div>
            </td>

            <!-- Analista -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-slate-300 text-xs sm:text-sm hidden md:table-cell max-w-[120px] truncate">
              {{ processo.analista || '—' }}
            </td>

            <!-- Status -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
              <StatusBadge :status="processo.status" />
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
                  @view="$emit('view', processo.id)"
                  @print="$emit('print', processo.id)"
                  @edit="$emit('edit', processo.id)"
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
  </div>
</template>

<script setup>
import DocumentIcon from '../../Icons/DocumentTextIcon.vue';
import PrazoBadge from '../../Molecules/Decretacoes/PrazoBadge.vue';
import StatusBadge from '../../Molecules/Decretacoes/StatusBadge.vue';
import TipoProcessoBadge from '../../Molecules/Decretacoes/TipoProcessoBadge.vue';
import TableActions from '../../Molecules/Table/TableActions.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Processos de Decretação',
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

defineEmits(['view', 'print', 'edit']);

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('pt-BR');
};
</script>
