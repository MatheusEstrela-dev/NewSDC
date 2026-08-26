<script setup>
import { computed } from 'vue';

const props = defineProps({
  percentual: {
    type: Number,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
  altura: {
    type: String,
    default: 'h-32',
  },
});

// Faixas de cor: critico ate 25, atencao ate 50, saudavel acima.
const corPreenchimento = computed(() => {
  if (props.percentual <= 25) return 'bg-red-500 dark:bg-red-600';
  if (props.percentual <= 50) return 'bg-amber-500 dark:bg-amber-600';
  return 'bg-emerald-500 dark:bg-emerald-600';
});

const corTexto = computed(() => {
  if (props.percentual <= 25) return 'text-red-700 dark:text-red-300';
  if (props.percentual <= 50) return 'text-amber-700 dark:text-amber-300';
  return 'text-emerald-700 dark:text-emerald-300';
});

const semCombustivel = computed(() => props.percentual === 0);
</script>

<template>
  <div class="flex flex-col items-center gap-1.5">
    <div
      class="relative w-14 overflow-hidden rounded-md bg-gray-200 dark:bg-gray-700"
      :class="altura"
      role="meter"
      :aria-valuenow="percentual"
      aria-valuemin="0"
      aria-valuemax="100"
      :aria-label="`Nivel de combustivel ${label}`"
    >
      <div
        class="absolute bottom-0 left-0 w-full transition-all duration-300"
        :class="corPreenchimento"
        :style="{ height: `${percentual}%` }"
      />
      <span
        class="absolute inset-x-0 top-1 text-center text-xs font-bold"
        :class="corTexto"
      >
        {{ label }}
      </span>
    </div>

    <span
      v-if="semCombustivel"
      class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-red-700 dark:bg-red-900/40 dark:text-red-300"
    >
      Sem combustivel
    </span>
  </div>
</template>
