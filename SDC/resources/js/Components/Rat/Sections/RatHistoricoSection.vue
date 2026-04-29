<template>
  <div class="space-y-6">
    <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
      <!-- Card 1: Histórico -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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
            v-model="localData.historico"
            @blur="handleEmitUpdate"
            placeholder="Descreva aqui o histórico completo..."
            :rows="12"
          />
        </div>
      </div>

      <!-- Card 2: Condições e Resultado -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 005-5v-2a5 5 0 00-5-5H7a4 4 0 00-4 4M3 9a4 4 0 014-4h9"/></svg>
          </div>
          <div>
            <h3 class="rat-section-title">Condições e Resultado</h3>
          </div>
        </div>

        <div class="rat-section-content">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-950/30 border border-slate-200 dark:border-slate-700/30">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Clima</label>
              <div class="grid grid-cols-2 gap-2">
                <div v-for="(val, key) in localData.clima" :key="key" class="flex items-center gap-2">
                  <input type="checkbox" :id="`clima-${key}`" v-model="localData.clima[key]" class="w-4 h-4 rounded text-blue-500" />
                  <label :for="`clima-${key}`" class="text-xs text-slate-600 dark:text-slate-400 capitalize">{{ key.replace('_', ' ') }}</label>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <FormSelect label="Resultado da Operação" v-model="localData.resultado" @blur="handleEmitUpdate" :options="resultadoOptions" />
              <FormSelect label="Grau de Risco" v-model="localData.grau_risco" @blur="handleEmitUpdate" :options="riscoOptions" />
            </div>
          </div>
        </div>
      </div>

      <!-- Card 3: Métricas -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <div>
            <h3 class="rat-section-title">Métricas e Encaminhamentos</h3>
          </div>
        </div>

        <div class="rat-section-content">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="(val, key) in localData.metricas" :key="key" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-950/30 border border-slate-200 dark:border-slate-700/30 text-center">
              <label class="block text-xs text-slate-500 mb-1 capitalize">{{ key.replace('_', ' ') }}</label>
              <input type="number" v-model="localData.metricas[key]" class="w-full bg-transparent text-center font-bold text-slate-800 dark:text-white" />
            </div>
          </div>
          <div class="mt-6">
            <FormField type="textarea" label="Encaminhamentos" v-model="localData.encaminhamentos" @blur="handleEmitUpdate" :rows="3" />
          </div>
        </div>
      </div>
    </fieldset>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import FormField from '../../Form/FormField.vue';
import FormSelect from '../../Form/FormSelect.vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  viewOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const localData = ref({
  historico: props.modelValue?.historico || '',
  clima: {
    chuva: props.modelValue?.clima?.chuva || false,
    vento_forte: props.modelValue?.clima?.vento_forte || false,
    nevoeiro: props.modelValue?.clima?.nevoeiro || false,
    tempestade: props.modelValue?.clima?.tempestade || false,
  },
  resultado: props.modelValue?.resultado || '',
  grau_risco: props.modelValue?.grau_risco || '',
  metricas: {
    pessoas_atendidas: props.modelValue?.metricas?.pessoas_atendidas || 0,
    vitimas_resgatadas: props.modelValue?.metricas?.vitimas_resgatadas || 0,
    imoveis_vistoriados: props.modelValue?.metricas?.imoveis_vistoriados || 0,
    familias_desalojadas: props.modelValue?.metricas?.familias_desalojadas || 0,
  },
  encaminhamentos: props.modelValue?.encaminhamentos || '',
});

const resultadoOptions = [
  { value: 'sucesso_total', label: 'Sucesso Total' },
  { value: 'sucesso_parcial', label: 'Sucesso Parcial' },
  { value: 'nao_localizado', label: 'Não Localizado' },
];

const riscoOptions = [
  { value: 'baixo', label: 'Baixo' },
  { value: 'medio', label: 'Médio' },
  { value: 'alto', label: 'Alto' },
];

// CORRIGIDO: Remover watchers infinitos que causavam FREEZE/TRAVA
// O problema: A cada digitação, watcher 1 emitia, que atualizava props,
// que triggerava watcher 2, que atualizava localData, criando um loop infinito
watch(
  () => props.modelValue,
  (nv) => {
    if (nv) {
      localData.value = {
        historico: nv.historico || '',
        clima: nv.clima || { chuva: false, vento_forte: false, nevoeiro: false, tempestade: false },
        resultado: nv.resultado || '',
        grau_risco: nv.grau_risco || '',
        metricas: nv.metricas || { pessoas_atendidas: 0, vitimas_resgatadas: 0, imoveis_vistoriados: 0, familias_desalojadas: 0 },
        encaminhamentos: nv.encaminhamentos || '',
      };
    }
  },
  { deep: false }
);

// Emitir apenas ao sair do campo (onBlur)
const handleEmitUpdate = () => {
  emit('update:modelValue', localData.value);
};
</script>
