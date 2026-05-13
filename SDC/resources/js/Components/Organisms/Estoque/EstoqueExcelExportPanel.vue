<template>
  <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">Export Excel</h3>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Previa das planilhas do MVP antes da exportacao</p>
        </div>
        <ActionButton
          module="estoque"
          resource="produtos"
          action="export"
          :allowed="canExport"
          size="sm"
          label="Gerar Excel"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 p-4 xl:grid-cols-[18rem_1fr]">
      <div class="space-y-2">
        <button
          v-for="sheet in sheets"
          :key="sheet.id"
          type="button"
          class="w-full rounded-lg border px-3 py-3 text-left transition"
          :class="sheet.id === activeSheet.id
            ? 'border-blue-400 bg-blue-50 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/15 dark:text-blue-200'
            : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-blue-300 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300'"
          @click="activeSheetId = sheet.id"
        >
          <div class="text-sm font-semibold">{{ sheet.nome }}</div>
          <div class="mt-1 text-xs opacity-75">{{ sheet.linhas }} linhas / {{ sheet.colunas.length }} colunas</div>
        </button>
      </div>

      <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between border-b border-slate-200 bg-emerald-50 px-3 py-2 dark:border-slate-700 dark:bg-emerald-500/10">
          <div class="min-w-0">
            <div class="truncate text-sm font-bold text-emerald-800 dark:text-emerald-200">{{ activeSheet.nome }}</div>
            <div class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Formato .xlsx com abas e cabecalho congelado</div>
          </div>
          <TableCellsIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[780px] text-sm">
            <thead>
              <tr class="bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                <th
                  v-for="coluna in activeSheet.colunas"
                  :key="coluna"
                  class="border-r border-slate-200 px-3 py-3 text-left last:border-r-0 dark:border-slate-700"
                >
                  {{ coluna }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr v-for="(linha, rowIndex) in activeSheet.amostra" :key="rowIndex" class="bg-white dark:bg-slate-900/60">
                <td
                  v-for="(cell, cellIndex) in linha"
                  :key="`${rowIndex}-${cellIndex}`"
                  class="border-r border-slate-200 px-3 py-3 text-slate-700 last:border-r-0 dark:border-slate-800 dark:text-slate-300"
                >
                  {{ cell }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import { TableCellsIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  sheets: {
    type: Array,
    default: () => [],
  },
  canExport: {
    type: Boolean,
    default: false,
  },
});

const activeSheetId = ref(null);

const activeSheet = computed(() => {
  return props.sheets.find((sheet) => sheet.id === activeSheetId.value) || props.sheets[0] || {
    id: null,
    nome: 'Planilha',
    linhas: 0,
    colunas: [],
    amostra: [],
  };
});

watch(
  () => props.sheets,
  (sheets) => {
    activeSheetId.value = sheets[0]?.id ?? null;
  },
  { immediate: true }
);
</script>
