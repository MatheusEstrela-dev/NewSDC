<script setup>
/**
 * Modal do botao "Exportar por REDEC".
 *
 * Espelha o ExportCsvModal (escopo por periodo ou serie completa) e acrescenta a
 * escolha da REDEC. Componente proprio, e nao uma prop do ExportCsvModal, para
 * nao mexer nos outros modulos que consomem aquele modal.
 */
import { ref, computed, watch } from 'vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  CalendarDaysIcon,
  ClockIcon,
  MapIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  show: Boolean,
  // Opcoes vindas de ProcessoFilter::getFilterOptions() -> Redec::toSelectOptions()
  redecs: {
    type: Array,
    default: () => [],
  },
  // REDEC ja filtrada na listagem, usada como valor inicial.
  redecSelecionada: {
    type: [String, Number],
    default: '',
  },
});

const emit = defineEmits(['close', 'export']);

const exportType = ref('all');
const redecId = ref('');
const dataInicio = ref('');
const dataFim = ref('');
const isExporting = ref(false);

// Reabrir o modal reaproveita a REDEC que esta filtrada na tela: o caso comum e
// "estou vendo a 3a REDEC, quero o CSV dela".
watch(() => props.show, (aberto) => {
  if (aberto) {
    exportType.value = 'all';
    redecId.value = props.redecSelecionada ? String(props.redecSelecionada) : '';
    dataInicio.value = '';
    dataFim.value = '';
    isExporting.value = false;
  }
});

const redecOptions = computed(() => props.redecs || []);

const redecLabel = computed(() => {
  if (!redecId.value) return 'Todas as REDECs';
  const opcao = redecOptions.value.find(o => String(o.id ?? o.value) === String(redecId.value));
  return opcao?.label ?? `REDEC ${redecId.value}`;
});

// Periodo exige as duas datas; serie completa nao exige nada. A REDEC e
// opcional de proposito: "Todas as REDECs" gera o CSV agrupado por REDEC.
const canExport = computed(() => {
  if (exportType.value === 'all') return true;
  return !!dataInicio.value && !!dataFim.value;
});

const close = () => emit('close');

const handleExport = () => {
  if (!canExport.value || isExporting.value) return;

  isExporting.value = true;

  emit('export', {
    type: exportType.value,
    all: exportType.value === 'all',
    redec_id: redecId.value || null,
    data_inicio: exportType.value === 'period' ? dataInicio.value : null,
    data_fim: exportType.value === 'period' ? dataFim.value : null,
  });

  setTimeout(() => {
    isExporting.value = false;
    close();
  }, 1000);
};
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="show" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close"></div>

      <div class="relative bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 dark:bg-indigo-700 p-5 text-white flex justify-between items-center">
          <div class="flex items-center gap-3">
            <div class="bg-white/20 p-2 rounded-lg">
              <MapIcon class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold">Exportar por REDEC</h2>
              <p class="text-indigo-100 text-xs">Formato CSV</p>
            </div>
          </div>
          <button @click="close" class="p-2 hover:bg-white/10 rounded-full transition-colors" aria-label="Fechar">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Conteudo -->
        <div class="p-6 space-y-6">
          <!-- REDEC -->
          <div class="space-y-1.5">
            <label for="export-redec" class="text-sm font-semibold text-slate-700 dark:text-slate-300">
              REDEC
            </label>
            <select
              id="export-redec"
              v-model="redecId"
              class="atom-input atom-select atom-input-md atom-input-normal w-full"
            >
              <option value="">Todas as REDECs</option>
              <option
                v-for="opcao in redecOptions"
                :key="opcao.id ?? opcao.value"
                :value="String(opcao.id ?? opcao.value)"
              >
                {{ opcao.label }}
              </option>
            </select>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Sem escolher uma REDEC o arquivo sai com todas, agrupadas por REDEC.
            </p>
          </div>

          <!-- Escopo -->
          <div class="space-y-3">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
              Selecione o escopo da exportação:
            </label>

            <label
              class="flex items-start gap-3 p-4 rounded-xl cursor-pointer border transition-all"
              :class="exportType === 'all'
                ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-300 dark:border-indigo-700'
                : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300'"
            >
              <input type="radio" value="all" v-model="exportType" class="mt-1 w-4 h-4 text-indigo-600 focus:ring-indigo-500" />
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <ClockIcon class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                  <span class="font-medium text-slate-800 dark:text-slate-200">Toda Série Histórica</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                  Todas as decretações da REDEC, sem recorte de data
                </p>
              </div>
            </label>

            <label
              class="flex items-start gap-3 p-4 rounded-xl cursor-pointer border transition-all"
              :class="exportType === 'period'
                ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-300 dark:border-indigo-700'
                : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300'"
            >
              <input type="radio" value="period" v-model="exportType" class="mt-1 w-4 h-4 text-indigo-600 focus:ring-indigo-500" />
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <CalendarDaysIcon class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                  <span class="font-medium text-slate-800 dark:text-slate-200">Período Específico</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                  Decretações com data de entrada no intervalo
                </p>
              </div>
            </label>
          </div>

          <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
          >
            <div v-if="exportType === 'period'" class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Data Inicial</label>
                <DatePicker v-model="dataInicio" class="w-full" />
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Data Final</label>
                <DatePicker v-model="dataFim" class="w-full" />
              </div>
            </div>
          </Transition>

          <p class="text-xs text-slate-500 dark:text-slate-400">
            Será exportado: <span class="font-medium text-slate-700 dark:text-slate-300">{{ redecLabel }}</span>
          </p>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex gap-3">
          <button
            @click="close"
            class="flex-1 py-2.5 px-4 rounded-lg text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-600"
          >
            Cancelar
          </button>
          <button
            @click="handleExport"
            :disabled="!canExport || isExporting"
            class="flex-[2] py-2.5 px-4 rounded-lg font-semibold transition-all flex items-center justify-center gap-2"
            :class="canExport && !isExporting
              ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md active:scale-[0.98]'
              : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed'"
          >
            <ArrowDownTrayIcon v-if="!isExporting" class="w-5 h-5" />
            <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            {{ isExporting ? 'Exportando...' : 'Exportar CSV' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
