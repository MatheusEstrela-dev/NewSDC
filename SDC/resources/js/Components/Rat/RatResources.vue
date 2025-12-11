<template>
  <div class="animate-fade-in-up">
    <RatResourcesSection v-model="localRecurso" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatResourcesSection from './Sections/RatResourcesSection.vue';

const props = defineProps({
  recursos: {
    type: [Array, Object],
    default: () => ({}),
  },
});

const emit = defineEmits(['add', 'remove', 'update']);

// Inicializa com um objeto vazio se recursos for array vazio
const localRecurso = ref(
  Array.isArray(props.recursos) && props.recursos.length === 0
    ? {
        tipo_recurso: '',
        categoria: '',
        orgao_responsavel: '',
        identificacao: '',
        condutor: '',
        descricao: '',
        data_saida: '',
        data_chegada: '',
        km_percorrido: '',
        local_origem: '',
        local_destino: '',
        quantidade: '1',
        capacidade: '',
        condicao: 'operacional',
        operador: '',
        contato_emergencia: '',
        observacoes: '',
        agentes: [],
      }
    : props.recursos
);

watch(
  localRecurso,
  (newValue) => {
    emit('update', newValue);
  },
  { deep: true }
);

watch(
  () => props.recursos,
  (newValue) => {
    if (newValue && !Array.isArray(newValue)) {
      localRecurso.value = { ...newValue };
    }
  },
  { deep: true }
);
</script>
