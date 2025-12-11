<template>
  <div class="rat-section-card">
    <!-- Header -->
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-default">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <div>
        <h3 class="rat-section-title">Histórico da Ocorrência</h3>
        <p class="text-xs text-slate-500 mt-0.5">
          Descreva o histórico completo da ocorrência, ações realizadas e resultados obtidos
        </p>
      </div>
    </div>

    <!-- Textarea Principal -->
    <div class="rat-section-content">
      <FormField
        type="textarea"
        v-model="localData.historico"
        placeholder="Descreva detalhadamente o histórico da ocorrência:&#10;&#10;- Como os fatos ocorreram&#10;- Ações realizadas pela equipe&#10;- Recursos utilizados&#10;- Resultados obtidos&#10;- Providências tomadas&#10;- Encaminhamentos realizados&#10;- Observações relevantes"
        :rows="15"
        class="min-h-[400px]"
      />

      <!-- Dicas de Preenchimento -->
      <div class="mt-4 p-4 rounded-lg bg-blue-500/10 border border-blue-500/30">
        <div class="flex gap-3">
          <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="flex-1">
            <h4 class="text-sm font-medium text-blue-400 mb-2">Dicas para um bom histórico:</h4>
            <ul class="text-xs text-slate-400 space-y-1">
              <li>• Seja objetivo e claro na descrição dos fatos</li>
              <li>• Utilize ordem cronológica dos acontecimentos</li>
              <li>• Mencione todos os recursos e agentes envolvidos</li>
              <li>• Descreva as ações técnicas realizadas</li>
              <li>• Registre condições climáticas se relevante</li>
              <li>• Informe encaminhamentos para outros órgãos</li>
              <li>• Documente resultados e impactos das ações</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Informações Complementares -->
      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Condições Climáticas -->
        <div class="p-4 rounded-lg bg-slate-950/30 border border-slate-700/30">
          <label class="block text-sm font-medium text-slate-300 mb-3">
            Condições Climáticas
          </label>
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                id="clima-chuva"
                v-model="localData.clima.chuva"
                class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500"
              />
              <label for="clima-chuva" class="text-sm text-slate-400">Chuva</label>
            </div>
            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                id="clima-vento"
                v-model="localData.clima.vento_forte"
                class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500"
              />
              <label for="clima-vento" class="text-sm text-slate-400">Vento Forte</label>
            </div>
            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                id="clima-nevoeiro"
                v-model="localData.clima.nevoeiro"
                class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500"
              />
              <label for="clima-nevoeiro" class="text-sm text-slate-400">Nevoeiro</label>
            </div>
            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                id="clima-tempestade"
                v-model="localData.clima.tempestade"
                class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500"
              />
              <label for="clima-tempestade" class="text-sm text-slate-400">Tempestade</label>
            </div>
          </div>
        </div>

        <!-- Resultado da Operação -->
        <div class="p-4 rounded-lg bg-slate-950/30 border border-slate-700/30">
          <label class="block text-sm font-medium text-slate-300 mb-3">
            Resultado da Operação
          </label>
          <select
            v-model="localData.resultado"
            class="w-full px-3 py-2 rounded-lg bg-slate-900/50 text-slate-200 border border-slate-700/50 hover:border-slate-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
          >
            <option value="">Selecione...</option>
            <option value="sucesso_total">Sucesso Total</option>
            <option value="sucesso_parcial">Sucesso Parcial</option>
            <option value="em_andamento">Em Andamento</option>
            <option value="encaminhado">Encaminhado a Outro Órgão</option>
            <option value="nao_localizado">Não Localizado</option>
          </select>

          <div class="mt-3">
            <label class="block text-xs text-slate-500 mb-2">Grau de Risco</label>
            <select
              v-model="localData.grau_risco"
              class="w-full px-3 py-2 rounded-lg bg-slate-900/50 text-slate-200 border border-slate-700/50 hover:border-slate-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all text-sm"
            >
              <option value="">Selecione...</option>
              <option value="baixo">Baixo</option>
              <option value="medio">Médio</option>
              <option value="alto">Alto</option>
              <option value="muito_alto">Muito Alto</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Métricas da Operação -->
      <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 rounded-lg bg-slate-950/30 border border-slate-700/30">
          <label class="block text-xs text-slate-500 mb-2">Pessoas Atendidas</label>
          <input
            type="number"
            v-model="localData.metricas.pessoas_atendidas"
            min="0"
            class="w-full px-3 py-2 rounded-lg bg-slate-900/50 text-slate-200 border border-slate-700/50 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
          />
        </div>
        <div class="p-4 rounded-lg bg-slate-950/30 border border-slate-700/30">
          <label class="block text-xs text-slate-500 mb-2">Vítimas Resgatadas</label>
          <input
            type="number"
            v-model="localData.metricas.vitimas_resgatadas"
            min="0"
            class="w-full px-3 py-2 rounded-lg bg-slate-900/50 text-slate-200 border border-slate-700/50 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
          />
        </div>
        <div class="p-4 rounded-lg bg-slate-950/30 border border-slate-700/30">
          <label class="block text-xs text-slate-500 mb-2">Imóveis Vistoriados</label>
          <input
            type="number"
            v-model="localData.metricas.imoveis_vistoriados"
            min="0"
            class="w-full px-3 py-2 rounded-lg bg-slate-900/50 text-slate-200 border border-slate-700/50 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
          />
        </div>
        <div class="p-4 rounded-lg bg-slate-950/30 border border-slate-700/30">
          <label class="block text-xs text-slate-500 mb-2">Famílias Desalojadas</label>
          <input
            type="number"
            v-model="localData.metricas.familias_desalojadas"
            min="0"
            class="w-full px-3 py-2 rounded-lg bg-slate-900/50 text-slate-200 border border-slate-700/50 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
          />
        </div>
      </div>

      <!-- Encaminhamentos -->
      <div class="mt-6">
        <label class="block text-sm font-medium text-slate-300 mb-3">
          Encaminhamentos Realizados
        </label>
        <FormField
          type="textarea"
          v-model="localData.encaminhamentos"
          placeholder="Liste os encaminhamentos realizados para outros órgãos ou setores..."
          :rows="4"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import FormField from '../../Form/FormField.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
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

watch(
  localData,
  (newValue) => {
    emit('update:modelValue', newValue);
  },
  { deep: true }
);

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      Object.assign(localData.value, newValue);
    }
  },
  { deep: true }
);
</script>
