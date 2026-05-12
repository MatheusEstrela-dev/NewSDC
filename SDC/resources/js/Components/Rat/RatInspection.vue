<template>
  <div class="animate-fade-in-up pb-6">
    <RatVistoriaSection v-model="localVistoria" :view-only="viewOnly" />

    <RatFormActions :view-only="viewOnly" :loading="loading" label="Salvar Vistoria" @save="$emit('save')" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatVistoriaSection from './Sections/RatVistoriaSection.vue';
import RatFormActions from '@/Components/Molecules/Rat/RatFormActions.vue';

const props = defineProps({
  vistoria: {
    type: Object,
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

const emit = defineEmits(['save', 'update']);

const localVistoria = ref(props.vistoria || {});

// Sincroniza local -> pai apenas se houver mudança real
watch(
  () => localVistoria.value,
  (newValue) => {
    if (JSON.stringify(newValue) !== JSON.stringify(props.vistoria)) {
      emit('update', newValue);
    }
  },
  { deep: true }
);

// Sincroniza pai -> local apenas se os dados externos mudarem
watch(
  () => props.vistoria,
  (newValue) => {
    if (newValue && JSON.stringify(newValue) !== JSON.stringify(localVistoria.value)) {
      localVistoria.value = JSON.parse(JSON.stringify(newValue));
    }
  },
  { deep: false }
);
</script>
