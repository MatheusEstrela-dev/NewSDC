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

    <!-- Botão Adicionar -->
    <div v-if="!viewOnly" class="flex justify-center">
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
    </div>

    <RatFormActions :view-only="viewOnly" :loading="loading" label="Salvar Recursos Empregados" @save="$emit('save')" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatResourcesSection from './Sections/RatResourcesSection.vue';
import RatFormActions from '@/Components/Molecules/Rat/RatFormActions.vue';

const props = defineProps({
  recursos: {
    type: [Array, Object],
    default: () => ({}),
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
        quantidade: '',
        capacidade: '',
        condicao: '',
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
    condicao: '',
    quantidade: ''
  });
};

const removerLocal = (index) => {
  localRecursos.value.splice(index, 1);
};

// Watch para emitir mudanças do localRecursos para o pai apenas se houver mudança real
watch(
  () => localRecursos.value,
  (newValue) => {
    if (JSON.stringify(newValue) !== JSON.stringify(props.recursos)) {
      emit('update', newValue);
    }
  },
  { deep: true }
);

// Watch para sincronizar props.recursos com localRecursos apenas se houver mudança real
watch(
  () => props.recursos,
  (newValue) => {
    if (Array.isArray(newValue) && newValue.length > 0) {
      if (JSON.stringify(localRecursos.value) !== JSON.stringify(newValue)) {
        localRecursos.value = JSON.parse(JSON.stringify(newValue));
      }
    }
  },
  { deep: false }
);
</script>
