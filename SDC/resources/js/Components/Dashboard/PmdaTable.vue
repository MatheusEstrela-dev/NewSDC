<template>
  <div
    class="bg-white rounded-xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 flex flex-col"
  >
    <!-- Header Card -->
    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/30">
      <div>
        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-lg">
          <DocumentTextIcon class="w-5 h-5 text-blue-500" />
          {{ title }}
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">{{ subtitle }}</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="showFilters"
          class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
          title="Filtros"
          @click="$emit('filter')"
        >
          <FunnelIcon class="w-5 h-5" />
        </button>
        <span
          class="bg-slate-100 text-slate-600 text-xs font-bold px-2.5 py-1 rounded-md border border-slate-200"
        >
          {{ items.length }} Itens
        </span>
      </div>
    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto flex-1">
      <table class="w-full text-sm text-left">
        <thead class="text-xs text-slate-400 uppercase font-bold bg-slate-50 border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Protocolo</th>
            <th class="px-6 py-4">Município</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 hidden sm:table-cell">Data</th>
            <th v-if="showActions" class="px-6 py-4 text-right">Ação</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr
            v-for="item in items"
            :key="item.id"
            class="hover:bg-slate-50/80 transition-colors group"
          >
            <td class="px-6 py-4">
              <div class="font-medium text-slate-900">{{ item.protocolo }}</div>
              <div class="text-xs text-slate-400 sm:hidden">{{ item.data }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="text-slate-700 font-medium">{{ item.municipio }}</div>
              <div v-if="item.responsavel" class="text-[10px] text-slate-400 uppercase">
                Resp: {{ item.responsavel }}
              </div>
            </td>
            <td class="px-6 py-4">
              <span
                :class="[
                  getStatusColor(item.status),
                  'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border border-current/10',
                ]"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5 opacity-70"></span>
                {{ item.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-slate-500 text-xs hidden sm:table-cell font-mono">
              {{ item.data }}
            </td>
            <td v-if="showActions" class="px-6 py-4 text-right">
              <button
                @click="$emit('view-item', item)"
                class="text-slate-400 hover:text-blue-600 p-1.5 hover:bg-blue-50 rounded-md transition-colors"
              >
                <EyeIcon class="w-5 h-5" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer Tabela -->
    <div v-if="showFooter" class="px-6 py-3 border-t border-slate-100 bg-slate-50/50 flex justify-center">
      <button
        class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors"
        @click="$emit('view-all')"
      >
        Ver lista completa
      </button>
    </div>
  </div>
</template>

<script setup>
import { getStatusColor } from '../../utils/statusColors';
import DocumentTextIcon from '../Icons/DocumentTextIcon.vue';
import FunnelIcon from '../Icons/FunnelIcon.vue';
import EyeIcon from '../Icons/EyeIcon.vue';

defineProps({
  title: {
    type: String,
    default: 'PMDA em Análise',
  },
  subtitle: {
    type: String,
    default: 'Processos aguardando intervenção técnica',
  },
  items: {
    type: Array,
    required: true,
  },
  showFilters: {
    type: Boolean,
    default: true,
  },
  showActions: {
    type: Boolean,
    default: true,
  },
  showFooter: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['filter', 'view-item', 'view-all']);
</script>

