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
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right">Ações</th>
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

            <!-- Ações -->
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <button
                  type="button"
                  class="p-1.5 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 transition-all duration-200"
                  title="Visualizar"
                  @click="$emit('view', protocolo.id)"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  class="p-1.5 rounded-lg text-amber-400 hover:text-amber-300 hover:bg-amber-500/10 transition-all duration-200"
                  title="Editar"
                  @click="$emit('edit', protocolo.id)"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  class="p-1.5 rounded-lg text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10 transition-all duration-200"
                  title="Série Histórica"
                  @click="$emit('history', protocolo.id)"
                >
                  <ClockIcon class="w-4 h-4" />
                </button>
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
import StatusPill from '@/Components/Molecules/Pae/Protocolos/StatusPill.vue';
import PrazosPill from '@/Components/Molecules/Pae/Protocolos/PrazosPill.vue';
import EyeIcon from '@/Components/Icons/EyeIcon.vue';
import PencilIcon from '@/Components/Icons/PencilIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';

defineProps({
  protocolos: {
    type: Array,
    required: true,
  },
});

defineEmits(['view', 'edit', 'history']);
</script>
