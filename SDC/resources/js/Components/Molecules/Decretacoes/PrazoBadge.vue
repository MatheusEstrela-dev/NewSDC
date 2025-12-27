<template>
  <span v-if="diasRestantes !== null" :class="badgeClasses" :title="tooltipText">
    <ClockIcon class="w-2.5 h-2.5 sm:w-3 sm:h-3 inline-block mr-0.5 sm:mr-1" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';
import ClockIcon from '../../Icons/ClockIcon.vue';

const props = defineProps({
  diasRestantes: {
    type: Number,
    default: null,
  },
  dataVencimento: {
    type: String,
    default: null,
  },
});

const label = computed(() => {
  if (props.diasRestantes === null) return '—';
  if (props.diasRestantes === 0) return 'Vencido';
  if (props.diasRestantes === 1) return '1 dia';
  if (props.diasRestantes <= 30) return `${props.diasRestantes} dias`;
  return `${Math.floor(props.diasRestantes / 30)} meses`;
});

const badgeClasses = computed(() => {
  const baseClasses = 'px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold inline-block whitespace-nowrap';

  if (props.diasRestantes === null) {
    return `${baseClasses} bg-slate-500/20 text-slate-300 border border-slate-500/20`;
  }

  if (props.diasRestantes === 0) {
    return `${baseClasses} bg-red-500/20 text-red-300 border border-red-500/20`;
  }

  if (props.diasRestantes <= 15) {
    return `${baseClasses} bg-orange-500/20 text-orange-300 border border-orange-500/20`;
  }

  if (props.diasRestantes <= 30) {
    return `${baseClasses} bg-yellow-500/20 text-yellow-300 border border-yellow-500/20`;
  }

  return `${baseClasses} bg-green-500/20 text-green-300 border border-green-500/20`;
});

const tooltipText = computed(() => {
  if (!props.dataVencimento) return '';
  return `Vence em: ${new Date(props.dataVencimento).toLocaleDateString('pt-BR')}`;
});
</script>
