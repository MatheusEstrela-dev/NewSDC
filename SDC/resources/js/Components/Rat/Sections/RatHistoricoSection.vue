<template>
  <div class="space-y-6">
    <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
      <!-- Quadro de Histórico da Ocorrência -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <h3 class="rat-section-title">Histórico da Ocorrência</h3>
            <p class="text-xs text-slate-500 mt-0.5">Descreva detalhadamente as ações realizadas</p>
          </div>
        </div>

        <div class="rat-section-content">
          <FormField
            type="textarea"
            v-model="localHistorico"
            placeholder="Descreva aqui o histórico completo da ocorrência..."
            :rows="14"
            @input="handleEmitUpdate"
          />
        </div>
      </div>
    </fieldset>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import FormField from '../../Form/FormField.vue';

const props = defineProps({
  modelValue: { type: [Object, String, Array], default: () => ({}) },
  viewOnly:   { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

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

const localHistorico = ref(extractText(props.modelValue));

watch(
  () => props.modelValue,
  (nv) => { localHistorico.value = extractText(nv); },
  { deep: false }
);

function handleEmitUpdate() {
  emit('update:modelValue', localHistorico.value);
}
</script>
