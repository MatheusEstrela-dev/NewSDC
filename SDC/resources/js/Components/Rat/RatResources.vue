<template>
  <div class="animate-fade-in-up pb-6 space-y-6">
    <div v-for="(recurso, index) in localRecursos" :key="recurso.id || index" class="relative">
      <div v-if="localRecursos.length > 1" class="flex justify-between items-center mb-2 px-4">
        <span class="text-sm font-bold text-blue-600">Recurso #{{ index + 1 }}</span>
        <button 
          v-if="!viewOnly" 
          @click="removerLocal(index)" 
          class="text-red-500 hover:text-red-700 text-xs font-medium"
        >
          Remover Recurso
        </button>
      </div>
      <RatResourcesSection v-model="localRecursos[index]" :view-only="viewOnly" />
      <hr v-if="index < localRecursos.length - 1" class="my-8 border-slate-200 dark:border-slate-700" />
    </div>

    <!-- Controles -->
    <div v-if="!viewOnly" class="flex flex-col items-center gap-4 mt-8">
      <button
        type="button"
        @click="adicionarLocal"
        class="px-6 py-2 rounded-full border-2 border-dashed border-blue-400 text-blue-500 hover:bg-blue-50 transition-colors flex items-center gap-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Adicionar Outro Recurso
      </button>

      <div class="rat-actions-footer w-full">
        <div class="max-w-full mx-auto flex items-center justify-center gap-2 sm:gap-3 px-3 py-3 sm:px-6 sm:py-4">
          <button
            type="button"
            @click="$emit('save')"
            class="px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg text-xs sm:text-sm font-semibold bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:from-blue-500 hover:to-blue-400 shadow-lg shadow-blue-600/25 transition-all duration-200 flex items-center gap-1.5 sm:gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Salvar Tudo
          </button>
        </div>
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
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['add', 'remove', 'update', 'save']);

// Garantir que localRecursos seja sempre um Array
const localRecursos = ref(
  Array.isArray(props.recursos) && props.recursos.length > 0
    ? [...props.recursos]
    : [{
        id: Date.now(),
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
      }]
);

const adicionarLocal = () => {
  localRecursos.value.push({
    id: Date.now(),
    tipo_recurso: '',
    categoria: '',
    agentes: [],
    condicao: 'operacional',
    quantidade: '1'
  });
};

const removerLocal = (index) => {
  localRecursos.value.splice(index, 1);
};

// Watch para emitir mudanças do localRecursos para o pai
watch(
  localRecursos,
  (newValue) => {
    emit('update', newValue);
  },
  { deep: true }
);

// Watch para sincronizar props.recursos com localRecursos
watch(
  () => props.recursos,
  (newValue) => {
    if (Array.isArray(newValue) && newValue.length > 0) {
      const currentStr = JSON.stringify(localRecursos.value);
      const newStr = JSON.stringify(newValue);
      if (currentStr !== newStr) {
        localRecursos.value = [...newValue];
      }
    }
  },
  { deep: true }
);
</script>
