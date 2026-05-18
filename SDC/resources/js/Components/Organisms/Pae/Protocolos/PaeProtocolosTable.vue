<template>
  <ListSurface
    title="Protocolos PAE"
    subtitle="Gerencie os protocolos de análise de PAE"
    :count="protocolos.length"
    :icon="ClipboardDocumentListIcon"
  >
    <div class="overflow-x-auto -mx-px">
      <table class="w-full text-sm text-left">
        <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/50">
          <tr>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Protocolo</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Empreendedor / Estrutura</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Analista</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Datas</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Situação</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right w-72 min-w-72">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/30">
          <tr v-for="protocolo in protocolos" :key="protocolo.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors">
            <!-- Protocolo -->
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900 dark:text-white">#{{ protocolo.protocoloNumero }}</div>
            </td>

            <!-- Empreendedor / Estrutura -->
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900 dark:text-white truncate max-w-[200px]">{{ protocolo.empreendedor }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px]">{{ protocolo.estrutura }}</div>
            </td>

            <!-- Analista -->
            <td class="px-4 py-3">
              <div class="text-slate-700 dark:text-slate-300">{{ protocolo.analista }}</div>
            </td>

            <!-- Datas -->
            <td class="px-4 py-3">
              <div class="text-xs text-slate-600 dark:text-slate-400">
                <span class="font-medium">Entrada:</span> {{ protocolo.dataEntrada }}
              </div>
              <div class="text-xs text-slate-600 dark:text-slate-400 mt-1 flex items-center gap-2">
                <span class="font-medium">Limite:</span> {{ protocolo.limiteAnalise }}
                <PrazosPill :prazo="protocolo.prazo" class="scale-90 origin-left" />
              </div>
            </td>

            <!-- Situação -->
            <td class="px-4 py-3">
              <StatusPill :situacao="protocolo.situacao" />
            </td>

            <!-- Acoes -->
            <td class="px-4 py-3 w-72 min-w-72">
              <div class="flex items-center justify-end">
                <TableActions
                  :show-attachments="false"
                  :show-delete="canDelete"
                  :show-edit="canEdit"
                  :show-history="true"
                  :show-check="canCheck"
                  :show-pdf="canPdf"
                  :show-archive="true"
                  :show-options="true"
                  :show-assign="canAtribuir && isAssignableStatus(protocolo.situacao)"
                  @view="$emit('view', protocolo.id)"
                  @print="$emit('print', protocolo.id)"
                  @edit="$emit('edit', protocolo.id)"
                  @history="$emit('history', protocolo.id)"
                  @check="$emit('check', protocolo.id)"
                  @pdf="$emit('pdf', protocolo.id)"
                  @archive="$emit('archive', protocolo.id)"
                  @delete="$emit('delete', protocolo.id)"
                  @options="$emit('options', protocolo.id)"
                  @assign="$emit('assign', protocolo.id)"
                />
              </div>
            </td>
          </tr>
          <tr v-if="protocolos.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
              Nenhum protocolo encontrado
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </ListSurface>
</template>

<script setup>
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import PrazosPill from '@/Components/Molecules/Pae/Protocolos/PrazosPill.vue';
import StatusPill from '@/Components/Molecules/Pae/Protocolos/StatusPill.vue';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';
import ListSurface from '@/Components/Organisms/Table/ListSurface.vue';
import { isAssignableStatus } from '@/Composables/usePaeAssignableStatus';

defineProps({
  protocolos: {
    type: Array,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  canAtribuir: {
    type: Boolean,
    default: false,
  },
  canCheck: {
    type: Boolean,
    default: false,
  },
  canPdf: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['view', 'print', 'edit', 'history', 'check', 'pdf', 'archive', 'delete', 'options', 'assign']);
</script>
