<template>
  <div class="animate-fade-in-up pb-6">
    <RatHistoricoSection
      :model-value="localHistorico"
      :view-only="viewOnly"
      @update:model-value="handleSectionUpdate"
    />

    <RatFormActions
      :view-only="viewOnly"
      :loading="loading"
      label="Salvar Histórico"
      @save="$emit('save')"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatHistoricoSection from './Sections/RatHistoricoSection.vue';
import RatFormActions from '@/Components/Molecules/Rat/RatFormActions.vue';

const props = defineProps({
  events:   { type: [Array, Object, String], default: () => [] },
  viewOnly: { type: Boolean, default: false },
  loading:  { type: Boolean, default: false },
});

const emit = defineEmits(['update', 'save']);

function extractText(val) {
  if (!val) return '';
  if (typeof val === 'string') return val;
  if (Array.isArray(val)) {
    const first = val[0];
    if (typeof first === 'string') return first;
    if (first && typeof first === 'object') return first.historico ?? '';
    return '';
  }
  if (typeof val === 'object') return val.historico ?? '';
  return '';
}

const localHistorico = ref(extractText(props.events));

watch(
  () => props.events,
  (nv) => { localHistorico.value = extractText(nv); },
  { deep: false }
);

function handleSectionUpdate(text) {
  localHistorico.value = text;
  emit('update', text);
}
</script>
