<template>
  <div class="animate-fade-in-up">
    <RatAttachmentsSection v-model="localAnexos" @update:modelValue="handleUpdate" />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import RatAttachmentsSection from './Sections/RatAttachmentsSection.vue';

const props = defineProps({
  anexos: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['add', 'remove', 'update']);

const localAnexos = ref({
  anexos: props.anexos || [],
});

// Watch para sincronizar quando props mudarem externamente
watch(
  () => props.anexos,
  (newAnexos) => {
    if (newAnexos && Array.isArray(newAnexos)) {
      const currentIds = (localAnexos.value.anexos || []).map(a => a.id).filter(Boolean).sort().join(',');
      const newIds = newAnexos.map(a => a.id).filter(Boolean).sort().join(',');
      
      if (currentIds !== newIds) {
        localAnexos.value.anexos = [...newAnexos];
      }
    } else if (!newAnexos || newAnexos.length === 0) {
      localAnexos.value.anexos = [];
    }
  },
  { deep: false, immediate: true }
);

// Handler para atualizações vindas do RatAttachmentsSection
function handleUpdate(newValue) {
  if (newValue && newValue.anexos) {
    const previousAnexos = localAnexos.value.anexos || [];
    const updatedAnexos = newValue.anexos;
    
    // Detectar novos anexos adicionados
    const previousIds = previousAnexos.map(a => a.id).filter(Boolean);
    const currentIds = updatedAnexos.map(a => a.id).filter(Boolean);
    
    const newAnexos = updatedAnexos.filter(a => a.id && !previousIds.includes(a.id));
    const removedIds = previousIds.filter(id => !currentIds.includes(id));
    
    // Atualizar estado local
    localAnexos.value.anexos = [...updatedAnexos];
    
    // Emitir eventos para o parent
    if (newAnexos.length > 0) {
      newAnexos.forEach(anexo => emit('add', anexo));
    }
    
    if (removedIds.length > 0) {
      removedIds.forEach(id => emit('remove', id));
    }
    
    // Emitir update geral
    emit('update', updatedAnexos);
  }
}
</script>

