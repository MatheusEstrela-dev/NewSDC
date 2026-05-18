<template>
  <div class="animate-fade-in-up pb-6 space-y-6">
    <div v-for="(envolvido, index) in localEnvolvidos" :key="envolvido.id || index" class="relative">
      <div v-if="localEnvolvidos.length > 1" class="flex justify-between items-center mb-2 px-4">
        <span class="text-sm font-bold text-blue-600">Envolvido #{{ index + 1 }}</span>
        <button 
          v-if="!viewOnly" 
          @click="removerLocal(index)" 
          class="text-red-500 hover:text-red-700 text-xs font-medium"
        >
          Remover Envolvido
        </button>
      </div>
      <RatEnvolvidosSection v-model="localEnvolvidos[index]" :view-only="viewOnly" />
      <hr v-if="index < localEnvolvidos.length - 1" class="my-8 border-slate-200 dark:border-slate-700" />
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
        Adicionar Outra Pessoa
      </button>
    </div>

    <RatFormActions :view-only="viewOnly" :loading="loading" label="Salvar Envolvidos" @save="handleSave" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatEnvolvidosSection from './Sections/RatEnvolvidosSection.vue';
import RatFormActions from '@/Components/Molecules/Rat/RatFormActions.vue';

const props = defineProps({
  envolvidos: {
    type: Array,
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

const emit = defineEmits(['add', 'remove', 'update', 'save']);

const localEnvolvidos = ref(
  Array.isArray(props.envolvidos) && props.envolvidos.length > 0
    ? [...props.envolvidos]
    : [{
        id: Date.now(),
        g_tipo_pessoa:   '',
        p_nome_completo: '',
        p_cpf:           '',
        p_sexo:          '',
        p_data_nascimento: '',
      }]
);

const adicionarLocal = () => {
  localEnvolvidos.value.push({
    id: Date.now(),
    g_tipo_pessoa:   '',
    p_nome_completo: '',
    p_sexo:          '',
  });
};

const removerLocal = (index) => {
  localEnvolvidos.value.splice(index, 1);
};

const handleSave = () => {
  emit('update', [...localEnvolvidos.value]);
  emit('save');
};

// Watch para emitir mudanças do localEnvolvidos para o pai apenas se houver mudança real
watch(
  () => localEnvolvidos.value,
  (newValue) => {
    if (JSON.stringify(newValue) !== JSON.stringify(props.envolvidos)) {
      emit('update', newValue);
    }
  },
  { deep: true }
);

// Watch para sincronizar props.envolvidos com localEnvolvidos apenas se houver mudança real
watch(
  () => props.envolvidos,
  (newValue) => {
    if (Array.isArray(newValue) && newValue.length > 0) {
      if (JSON.stringify(localEnvolvidos.value) !== JSON.stringify(newValue)) {
        localEnvolvidos.value = JSON.parse(JSON.stringify(newValue));
      }
    }
  },
  { deep: false }
);
</script>
