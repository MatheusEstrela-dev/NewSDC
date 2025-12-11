<template>
  <div class="animate-fade-in-up">
    <RatVistoriaSection v-model="localVistoria" />
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
});

const emit = defineEmits(['save', 'update']);

const localVistoria = ref(props.vistoria || {});

watch(
  localVistoria,
  (newValue) => {
    emit('update', newValue);
    emit('save', newValue);
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
