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
        Adicionar Outra Pessoa
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
import RatEnvolvidosSection from './Sections/RatEnvolvidosSection.vue';

const props = defineProps({
  envolvidos: {
    type: Array,
    default: () => [],
  },
  viewOnly: {
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
        g_tipo_pessoa: 'vítima',
        p_nome_completo: '',
        p_rg: '',
        p_cpf: '',
        p_sexo: '',
        p_data_nascimento: '',
      }]
);

const adicionarLocal = () => {
  localEnvolvidos.value.push({
    id: Date.now(),
    g_tipo_pessoa: 'vítima',
    p_nome_completo: '',
    p_sexo: '',
  });
};

const removerLocal = (index) => {
  localEnvolvidos.value.splice(index, 1);
};

watch(
  localEnvolvidos,
  (newValue) => {
    emit('update', newValue);
  },
  { deep: true }
);

watch(
  () => props.envolvidos,
  (newValue) => {
    if (Array.isArray(newValue) && newValue.length > 0) {
      const currentStr = JSON.stringify(localEnvolvidos.value);
      const newStr = JSON.stringify(newValue);
      if (currentStr !== newStr) {
        localEnvolvidos.value = [...newValue];
      }
    }
  },
  { deep: true }
);
</script>
