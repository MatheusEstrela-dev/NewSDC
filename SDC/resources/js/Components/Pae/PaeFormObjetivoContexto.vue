<template>
  <div class="space-y-6">
    <PaeCard title="2. Objetivo">
      <textarea
        v-model="local.objetivo"
        rows="6"
        placeholder="Descreva o objetivo da análise..."
        class="w-full bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-4 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
      />
    </PaeCard>

    <PaeCard title="3. Contextualização">
      <textarea
        v-model="local.contextualizacao"
        rows="8"
        placeholder="Contextualize a análise técnica..."
        class="w-full bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-4 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
      />
    </PaeCard>

    <div class="flex justify-end">
      <button
        type="button"
        :disabled="saving"
        @click="$emit('save', local)"
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        Salvar Objetivo e Contexto
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import PaeCard from './PaeCard.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save']);

const local = reactive({ ...props.modelValue });

watch(() => props.modelValue, (val) => Object.assign(local, val), { deep: true });
</script>
