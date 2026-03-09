<template>
  <div class="animate-fade-in-up pb-6">
    <RatResourcesSection v-model="localRecurso" />

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
          Salvar Recursos Empregados
        </button>
      </div>
    </div>
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

const emit = defineEmits(['add', 'remove', 'update', 'save']);

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

// Watch para emitir mudanças do localRecurso para o pai
watch(
  localRecurso,
  (newValue) => {
    emit('update', newValue);
  },
  { deep: true }
);

// Watch para sincronizar props.recursos com localRecurso, evitando loops infinitos
watch(
  () => props.recursos,
  (newValue) => {
    if (newValue && !Array.isArray(newValue)) {
      // Compara valores para evitar atualizações desnecessárias
      const currentStr = JSON.stringify(localRecurso.value);
      const newStr = JSON.stringify(newValue);

      // Só atualiza se houver diferença real
      if (currentStr !== newStr) {
        localRecurso.value = { ...newValue };
      }
    }
  },
  { deep: true }
);
</script>
