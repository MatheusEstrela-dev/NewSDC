<script setup>
import { ref, computed, watch } from 'vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import { 
  XMarkIcon, 
  ArrowDownTrayIcon,
  CalendarDaysIcon,
  ClockIcon,
  TableCellsIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  show: Boolean,
  moduleName: {
    type: String,
    default: 'Dados'
  },
  exportEndpoint: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'export']);

// Estado do modal
const exportType = ref('period'); // 'period' ou 'all'
const dataInicio = ref('');
const dataFim = ref('');
const isExporting = ref(false);

// Reset ao abrir
watch(() => props.show, (newVal) => {
  if (newVal) {
    exportType.value = 'period';
    dataInicio.value = '';
    dataFim.value = '';
    isExporting.value = false;
  }
});

// Validação
const canExport = computed(() => {
  if (exportType.value === 'all') return true;
  return dataInicio.value && dataFim.value;
});

// Fechar modal
const close = () => {
  emit('close');
};

// Executar exportação
const handleExport = async () => {
  if (!canExport.value) return;
  
  isExporting.value = true;
  
  const params = {
    type: exportType.value,
    data_inicio: exportType.value === 'period' ? dataInicio.value : null,
    data_fim: exportType.value === 'period' ? dataFim.value : null,
    all: exportType.value === 'all'
  };
  
  emit('export', params);
  
  // Simular delay e fechar
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
      <!-- Backdrop -->
      <div 
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
        @click="close"
      ></div>
      
      <!-- Modal -->
      <div class="relative bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        
        <!-- Header -->
        <div class="bg-emerald-600 dark:bg-emerald-700 p-5 text-white flex justify-between items-center">
          <div class="flex items-center gap-3">
            <div class="bg-white/20 p-2 rounded-lg">
              <TableCellsIcon class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold">Exportar {{ moduleName }}</h2>
              <p class="text-emerald-100 text-xs">Formato CSV</p>
            </div>
          </div>
          <button 
            @click="close"
            class="p-2 hover:bg-white/10 rounded-full transition-colors"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
          
          <!-- Opções de Tipo -->
          <div class="space-y-3">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">
              Selecione o escopo da exportação:
            </label>
            
            <!-- Período -->
            <label 
              class="flex items-start gap-3 p-4 rounded-xl cursor-pointer border transition-all"
              :class="exportType === 'period' 
                ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700' 
                : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300'"
            >
              <input 
                type="radio" 
                value="period" 
                v-model="exportType"
                class="mt-1 w-4 h-4 text-emerald-600 focus:ring-emerald-500"
              />
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <CalendarDaysIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                  <span class="font-medium text-slate-800 dark:text-slate-200">Período Específico</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                  Exportar dados de um intervalo de datas
                </p>
              </div>
            </label>

            <!-- Toda Série Histórica -->
            <label 
              class="flex items-start gap-3 p-4 rounded-xl cursor-pointer border transition-all"
              :class="exportType === 'all' 
                ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700' 
                : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300'"
            >
              <input 
                type="radio" 
                value="all" 
                v-model="exportType"
                class="mt-1 w-4 h-4 text-emerald-600 focus:ring-emerald-500"
              />
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <ClockIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                  <span class="font-medium text-slate-800 dark:text-slate-200">Toda Série Histórica</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                  Exportar todos os dados disponíveis no banco
                </p>
              </div>
            </label>
          </div>

          <!-- Campos de Data (apenas se período) -->
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
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">
                  Data Inicial
                </label>
                <DatePicker v-model="dataInicio" class="w-full" />
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">
                  Data Final
                </label>
                <DatePicker v-model="dataFim" class="w-full" />
              </div>
            </div>
          </Transition>

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
              ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md active:scale-[0.98]'
              : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed'"
          >
            <ArrowDownTrayIcon v-if="!isExporting" class="w-5 h-5" />
            <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            {{ isExporting ? 'Exportando...' : 'Exportar CSV' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
