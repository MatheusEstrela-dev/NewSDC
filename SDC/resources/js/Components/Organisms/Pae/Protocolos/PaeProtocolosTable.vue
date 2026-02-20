<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Protocolo</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Empreendedor / Estrutura</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Analista</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Datas</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Situação</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right w-44 min-w-44">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="protocolo in protocolos" :key="protocolo.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
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
            <td class="px-4 py-3 w-44 min-w-44">
              <div class="flex items-center justify-end">
                <TableActions
                  :show-attachments="false"
                  :show-delete="canDelete"
                  :show-edit="canEdit"
                  :show-history="true"
                  :show-archive="true"
                  @view="$emit('view', protocolo.id)"
                  @print="$emit('print', protocolo.id)"
                  @edit="$emit('edit', protocolo.id)"
                  @history="$emit('history', protocolo.id)"
                  @archive="$emit('archive', protocolo.id)"
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
  </div>
</template>

<script setup>
import PrazosPill from '@/Components/Molecules/Pae/Protocolos/PrazosPill.vue';
import StatusPill from '@/Components/Molecules/Pae/Protocolos/StatusPill.vue';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';

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
});

defineEmits(['view', 'print', 'edit', 'history', 'archive']);
</script>
