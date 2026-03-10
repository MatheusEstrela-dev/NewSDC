<template>
  <div class="animate-fade-in-up pb-6">
    <RatEnvolvidosSection v-model="localEnvolvidos" :view-only="viewOnly" />

    <!-- Footer de ações -->
    <div v-if="!viewOnly" class="rat-actions-footer mt-4">
      <div class="max-w-full mx-auto flex items-center justify-center gap-2 sm:gap-3 px-3 py-3 sm:px-6 sm:py-4">
        <button
          type="button"
          @click="$emit('save')"
          class="px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg text-xs sm:text-sm font-semibold bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:from-blue-500 hover:to-blue-400 shadow-lg shadow-blue-600/25 transition-all duration-200 flex items-center gap-1.5 sm:gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Salvar Envolvidos
        </button>
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

const localEnvolvidos = ref(props.envolvidos || []);

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
    if (newValue) {
      localEnvolvidos.value = [...newValue];
    }
  },
  { deep: true }
);
</script>
