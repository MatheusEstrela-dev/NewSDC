<template>
  <div class="animate-fade-in-up pb-6">
    <RatHistoricoSection v-model="localHistorico" />

    <!-- Footer de ações -->
    <div class="rat-actions-footer mt-4">
      <div class="max-w-full mx-auto flex items-center justify-center gap-2 sm:gap-3 px-3 py-3 sm:px-6 sm:py-4">
        <button
          type="button"
          @click="$emit('save')"
          class="px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg text-xs sm:text-sm font-semibold bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:from-blue-500 hover:to-blue-400 shadow-lg shadow-blue-600/25 transition-all duration-200 flex items-center gap-1.5 sm:gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Salvar Histórico
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatHistoricoSection from './Sections/RatHistoricoSection.vue';

const props = defineProps({
  events: {
    type: [Array, Object],
    default: () => [],
  },
});

const emit = defineEmits(['add-observation', 'update', 'save']);

// events can be an array (list of history events) or an object (historico data from backend)
const defaultHistorico = {
  historico: '',
  clima: {
    chuva: false,
    vento_forte: false,
    nevoeiro: false,
    tempestade: false,
  },
  resultado: '',
  grau_risco: '',
  metricas: {
    pessoas_atendidas: 0,
    vitimas_resgatadas: 0,
    imoveis_vistoriados: 0,
    familias_desalojadas: 0,
  },
  encaminhamentos: '',
};

function initFromProps(events) {
  if (!events) return { ...defaultHistorico };
  // If it's a non-empty object (not array), use it directly as historico data
  if (!Array.isArray(events) && typeof events === 'object' && Object.keys(events).length > 0) {
    return { ...defaultHistorico, ...events };
  }
  // If it's an array with an object as first element (stored as [{}])
  if (Array.isArray(events) && events.length === 1 && typeof events[0] === 'object') {
    return { ...defaultHistorico, ...events[0] };
  }
  return { ...defaultHistorico };
}

const localHistorico = ref(initFromProps(props.events));

watch(
  localHistorico,
  (newValue) => {
    emit('update', newValue);
  },
  { deep: true }
);

watch(
  () => props.events,
  (newValue) => {
    const initialized = initFromProps(newValue);
    const currentStr = JSON.stringify(localHistorico.value);
    const newStr = JSON.stringify(initialized);
    if (currentStr !== newStr) {
      localHistorico.value = initialized;
    }
  },
  { deep: true }
);
</script>
