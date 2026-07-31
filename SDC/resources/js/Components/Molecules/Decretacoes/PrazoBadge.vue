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

const VARIANT_CLASSES = {
  sem_vigencia: 'bg-slate-500/20 text-slate-300 border border-slate-500/20',
  vencido: 'bg-red-500/20 text-red-300 border border-red-500/20',
  critico: 'bg-orange-500/20 text-orange-300 border border-orange-500/20',
  alerta: 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/20',
  vigente: 'bg-green-500/20 text-green-300 border border-green-500/20',
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
