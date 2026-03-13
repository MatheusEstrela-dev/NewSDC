<template>
  <div class="animate-fade-in-up">
    <RatEnvolvidosSection v-model="localEnvolvidos" />
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
});

const emit = defineEmits(['add', 'remove', 'update']);

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
