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

// Mesma convencao do StatusBadge: tom -100/-700/-300 no tema claro e o par
// dark: preservando o visual escuro. Antes so existiam os tons de fundo escuro
// (text-*-300 sobre bg-*-500/20), que no card branco ficavam lavados e sem
// contraste suficiente para leitura.
const badgeClasses = computed(() => {
  const baseClasses = 'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap';

  if (props.diasRestantes === null) {
    return `${baseClasses} bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:border-slate-500/30`;
  }

  if (props.diasRestantes === 0) {
    return `${baseClasses} bg-red-100 text-red-700 border border-red-300 dark:bg-red-500/20 dark:text-red-300 dark:border-red-500/30`;
  }

  if (props.diasRestantes <= 15) {
    return `${baseClasses} bg-orange-100 text-orange-700 border border-orange-300 dark:bg-orange-500/20 dark:text-orange-300 dark:border-orange-500/30`;
  }

  // amber no lugar de yellow: o yellow-700 do Tailwind ainda rende pouco
  // contraste sobre branco.
  if (props.diasRestantes <= 30) {
    return `${baseClasses} bg-amber-100 text-amber-800 border border-amber-300 dark:bg-yellow-500/20 dark:text-yellow-300 dark:border-yellow-500/30`;
  }

  return `${baseClasses} bg-green-100 text-green-700 border border-green-300 dark:bg-green-500/20 dark:text-green-300 dark:border-green-500/30`;
});

const tooltipText = computed(() => {
  if (!props.dataVencimento) return '';
  return `Vence em: ${new Date(props.dataVencimento).toLocaleDateString('pt-BR')}`;
});
</script>
