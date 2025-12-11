<template>
  <div class="animate-fade-in-up">
    <RatHistoricoSection v-model="localHistorico" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatHistoricoSection from './Sections/RatHistoricoSection.vue';

const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['add-observation', 'update']);

const localHistorico = ref({
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
});

watch(
  localHistorico,
  (newValue) => {
    emit('update', newValue);
  },
  { deep: true }
);
</script>
