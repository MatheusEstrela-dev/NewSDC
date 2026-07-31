<script setup>
import { computed } from 'vue';
import { CalendarDaysIcon, ClockIcon, DocumentCheckIcon } from '@heroicons/vue/24/outline';
import {
  PRAZO_PADRAO_DIAS,
  formatarData,
  prazoEfetivo,
  rotuloDiasRestantes,
  situacaoVigencia,
} from '@/Composables/decretacoes/useVigencia';

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
});

function formatDate(date) {
  return formatarData(date) || 'N/A';
}

const vigencia = computed(() => props.processo?.vigencia || {});

/** Dias restantes assinados; null quando o decreto nao tem vigencia calculada. */
const diasRestantes = computed(() => {
  const valor = props.processo?.dias_restantes ?? vigencia.value?.dias_restantes ?? null;
  return valor === null || valor === undefined ? null : Number(valor);
});

/** Prazo usado no calculo: informado, ou o padrao de 180 dias. */
const prazoVigencia = computed(() => prazoEfetivo(
  props.processo?.prazo_vigencia_efetivo
    ?? props.processo?.prazo_vigencia
    ?? vigencia.value?.prazo_dias
    ?? PRAZO_PADRAO_DIAS,
));

const dataVencimento = computed(() => {
  return props.processo?.data_vencimento ?? vigencia.value?.data_vencimento;
});

const diasRestantesTexto = computed(() => (
  diasRestantes.value === null ? '—' : String(diasRestantes.value)
));

const situacao = computed(() => situacaoVigencia(diasRestantes.value));

const situacaoLabel = computed(() => rotuloDiasRestantes(diasRestantes.value));

const progressPercent = computed(() => {
  if (diasRestantes.value === null) return 0;

  const total = prazoVigencia.value;
  // Vencido: barra cheia. Vigente: proporcao do prazo ja decorrido.
  const decorridos = total - Math.max(0, diasRestantes.value);

  return Math.min(100, Math.max(0, (decorridos / total) * 100));
});

const TEXT_CLASSES = {
  sem_vigencia: 'text-slate-400',
  vencido: 'text-red-400',
  critico: 'text-amber-400',
  alerta: 'text-yellow-400',
  vigente: 'text-emerald-400',
};

const PROGRESS_CLASSES = {
  sem_vigencia: 'bg-slate-500',
  vencido: 'bg-red-500',
  critico: 'bg-amber-500',
  alerta: 'bg-yellow-500',
  vigente: 'bg-emerald-500',
};

const diasClass = computed(() => TEXT_CLASSES[situacao.value]);
const progressClass = computed(() => PROGRESS_CLASSES[situacao.value]);
</script>

<template>
  <div class="space-y-6">
    <!-- Vigencia Card -->
    <div class="flex flex-col md:flex-row gap-6">
      <!-- Contador de Dias -->
      <div class="flex-shrink-0 p-6 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm text-center md:w-48">
        <CalendarDaysIcon class="w-16 h-16 mx-auto text-cyan-500 dark:text-cyan-400 mb-3" />
        <p class="text-5xl font-bold" :class="diasClass">{{ diasRestantesTexto }}</p>
        <p class="text-slate-500 dark:text-slate-400 mt-1">Dias restantes</p>
        <p class="text-xs mt-1" :class="diasClass">{{ situacaoLabel }}</p>
      </div>

      <!-- Informacoes de Vigencia -->
      <div class="flex-1 p-6 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <ClockIcon class="w-5 h-5 text-slate-500 dark:text-slate-400" />
          <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Informacoes de Vigencia</span>
        </div>
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-slate-400">Prazo de Vigencia:</span>
            <span class="text-slate-800 dark:text-white font-medium">
              {{ prazoVigencia }} dias
              <span v-if="processo?.prazo_vigencia_padrao" class="text-xs text-slate-500">(padrao)</span>
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 dark:text-slate-400">Data de Vencimento:</span>
            <span class="text-slate-800 dark:text-white font-medium">{{ formatDate(dataVencimento) }}</span>
          </div>
          <!-- Progress Bar -->
          <div class="pt-2">
            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
              <div
                class="h-full transition-colors duration-500 rounded-full"
                :class="progressClass"
                :style="{ width: progressPercent + '%' }"
              />
            </div>
            <p class="text-xs text-slate-500 mt-1 text-right">{{ Math.round(progressPercent) }}% decorrido</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Dados do Decreto Municipal/Estadual -->
    <div class="p-6 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
      <div class="flex items-center gap-3 mb-4">
        <DocumentCheckIcon class="w-5 h-5 text-slate-500 dark:text-slate-400" />
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Dados do Decreto Municipal/Estadual</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <span class="text-slate-500 dark:text-slate-400 text-sm">N Decreto:</span>
          <p class="text-slate-800 dark:text-white font-medium">
            {{ processo.n_decreto_municipal || vigencia.decreto_municipal?.numero || 'N/A' }}
          </p>
        </div>
        <div>
          <span class="text-slate-500 dark:text-slate-400 text-sm">Data de Publicacao:</span>
          <p class="text-slate-800 dark:text-white font-medium">
            {{ formatDate(processo.data_publicacao_mg || vigencia.decreto_municipal?.data_publicacao) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Reconhecimento Oficial -->
    <div class="p-6 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
      <div class="flex items-center gap-3 mb-4">
        <DocumentCheckIcon class="w-5 h-5 text-emerald-500 dark:text-emerald-400" />
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Reconhecimento Oficial</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
        <!-- Decreto Estadual -->
        <div>
          <span class="text-slate-500 dark:text-slate-400 text-sm">Decreto Estadual:</span>
          <p class="text-slate-800 dark:text-white font-medium">
            {{ processo.n_decreto_estadual || vigencia.decreto_estadual?.numero || 'N/A' }}
            <span v-if="processo.data_decreto_estadual || vigencia.decreto_estadual?.data_publicacao" class="text-slate-500 dark:text-slate-400">
              , de {{ formatDate(processo.data_decreto_estadual || vigencia.decreto_estadual?.data_publicacao) }}
            </span>
          </p>
        </div>

        <!-- Publicacao DOMG -->
        <div>
          <span class="text-slate-500 dark:text-slate-400 text-sm">Publicacao DOMG:</span>
          <p class="text-slate-800 dark:text-white font-medium">
            {{ processo.n_edicao_domg || vigencia.decreto_estadual?.domg || 'N/A' }}
            <span v-if="processo.data_publicacao_domg" class="text-slate-500 dark:text-slate-400">
              , de {{ formatDate(processo.data_publicacao_domg) }}
            </span>
          </p>
        </div>

        <!-- Portaria Federal -->
        <div>
          <span class="text-slate-500 dark:text-slate-400 text-sm">Portaria Federal:</span>
          <p class="text-slate-800 dark:text-white font-medium">
            {{ processo.n_portaria_federal || vigencia.reconhecimento_federal?.portaria || 'N/A' }}
            <span v-if="processo.data_portaria_federal" class="text-slate-500 dark:text-slate-400">
              , de {{ formatDate(processo.data_portaria_federal) }}
            </span>
          </p>
        </div>

        <!-- Publicacao DOU -->
        <div>
          <span class="text-slate-500 dark:text-slate-400 text-sm">Publicacao DOU:</span>
          <p class="text-slate-800 dark:text-white font-medium">
            {{ processo.n_edicao_dou || 'N/A' }}
            <span v-if="processo.data_publicacao_dou || vigencia.reconhecimento_federal?.data_dou" class="text-slate-500 dark:text-slate-400">
              , de {{ formatDate(processo.data_publicacao_dou || vigencia.reconhecimento_federal?.data_dou) }}
            </span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
