<template>
  <div class="animate-fade-in-up pb-6">
    <RatVistoriaSection v-model="localVistoria" :view-only="viewOnly" />

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
          Salvar Vistoria
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatVistoriaSection from './Sections/RatVistoriaSection.vue';

const props = defineProps({
  vistoria: {
    type: Object,
    default: () => ({}),
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['save', 'update']);

const localVistoria = ref(props.vistoria || {});

watch(
  localVistoria,
  (newValue) => {
    emit('update', newValue);
  },
  { deep: true }
);

watch(
  () => props.vistoria,
  (newValue) => {
    if (newValue) {
      localVistoria.value = { ...newValue };
    }
  },
  { deep: true }
);
</script>
