<template>
  <div class="animate-fade-in-up">
    <RatHistoricoSection v-model="localHistorico" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatHistoricoSection from './Sections/RatHistoricoSection.vue';

const props = defineProps({
  historico: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update']);

const localHistorico = ref(
  props.historico && typeof props.historico === 'object' && !Array.isArray(props.historico)
    ? { ...props.historico }
    : {}
);

watch(
  localHistorico,
  (newValue) => {
    emit('update', newValue);
  },
  { deep: true }
);

watch(
  () => props.historico,
  (newValue) => {
    if (newValue && typeof newValue === 'object') {
      localHistorico.value = { ...newValue };
    }
  },
  { deep: true }
);
</script>

