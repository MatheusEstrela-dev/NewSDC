<template>
  <div class="animate-fade-in-up pb-6">
    <RatHistoricoSection :model-value="localHistorico" @update:model-value="handleSectionUpdate" :view-only="viewOnly" />

    <RatFormActions :view-only="viewOnly" :loading="loading" @save="$emit('save')" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatHistoricoSection from './Sections/RatHistoricoSection.vue';
import RatFormActions from '@/Components/Molecules/Rat/RatFormActions.vue';

const props = defineProps({
  events: {
    type: [Array, Object],
    default: () => [],
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
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
  
  // Se for uma string (formato legado ou simplificado do backend)
  if (typeof events === 'string') {
    return { ...defaultHistorico, historico: events };
  }

  // Se for um objeto (não array)
  if (!Array.isArray(events) && typeof events === 'object' && Object.keys(events).length > 0) {
    return { ...defaultHistorico, ...events };
  }
  
  // Se for um array com um objeto (armazenamento como [{}])
  if (Array.isArray(events) && events.length === 1 && typeof events[0] === 'object') {
    return { ...defaultHistorico, ...events[0] };
  }
  
  // Se for um array de strings ou outros formatos, tentamos extrair o conteúdo
  if (Array.isArray(events) && events.length > 0 && typeof events[0] === 'string') {
    return { ...defaultHistorico, historico: events[0] };
  }

  return { ...defaultHistorico };
}

// Usamos uma referência local para evitar loops de reatividade
const localHistorico = ref(initFromProps(props.events));

// Sincroniza local -> pai apenas quando necessário
function handleSectionUpdate(newValue) {
  localHistorico.value = newValue;
  emit('update', newValue);
}

// Sincroniza pai -> local apenas se os dados mudarem (ex: vindo do servidor)
watch(
  () => props.events,
  (newValue) => {
    if (newValue) {
      localHistorico.value = initFromProps(newValue);
    }
  },
  { deep: false } // Não precisa ser deep aqui se estamos apenas reiniciando o estado
);
</script>
