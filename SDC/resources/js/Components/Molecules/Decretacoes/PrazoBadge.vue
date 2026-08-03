<template>
  <span v-if="diasRestantes !== null" :class="badgeClasses" :title="tooltipText">
    <ClockIcon class="w-2.5 h-2.5 sm:w-3 sm:h-3 inline-block mr-0.5 sm:mr-1" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';
import ClockIcon from '../../Icons/ClockIcon.vue';
import {
  formatarData,
  rotuloDiasRestantes,
  situacaoVigencia,
} from '@/Composables/decretacoes/useVigencia';

const props = defineProps({
  // Dias restantes assinados: negativo = vencido, 0 = vence hoje, null = sem vigencia
  diasRestantes: {
    type: Number,
    default: null,
  },
  dataVencimento: {
    type: String,
    default: null,
  },
});

const label = computed(() => rotuloDiasRestantes(props.diasRestantes));

const BASE_CLASSES = 'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap';

// Par claro/escuro em cada variante, mesma convencao de Decretacoes/StatusBadge.
// Os tons -300 sobre bg-*-500/20 sao paleta de fundo escuro e ficavam ilegiveis
// sobre card branco. Em 'alerta', amber no lugar de yellow: o yellow-700 do
// Tailwind ainda rende pouco contraste sobre branco.
const VARIANT_CLASSES = {
  sem_vigencia: 'bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:border-slate-500/30',
  vencido: 'bg-red-100 text-red-700 border border-red-300 dark:bg-red-500/20 dark:text-red-300 dark:border-red-500/30',
  critico: 'bg-orange-100 text-orange-700 border border-orange-300 dark:bg-orange-500/20 dark:text-orange-300 dark:border-orange-500/30',
  alerta: 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-yellow-500/20 dark:text-yellow-300 dark:border-yellow-500/30',
  vigente: 'bg-green-100 text-green-700 border border-green-300 dark:bg-green-500/20 dark:text-green-300 dark:border-green-500/30',
};

const badgeClasses = computed(() => {
  const variante = situacaoVigencia(props.diasRestantes);
  return `${BASE_CLASSES} ${VARIANT_CLASSES[variante]}`;
});

const tooltipText = computed(() => {
  if (!props.dataVencimento) return '';

  const data = formatarData(props.dataVencimento);
  if (!data) return '';

  return props.diasRestantes !== null && props.diasRestantes < 0
    ? `Venceu em: ${data}`
    : `Vence em: ${data}`;
});
</script>
