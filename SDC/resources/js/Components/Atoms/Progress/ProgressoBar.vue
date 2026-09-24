<template>
  <div class="w-full max-w-[160px]" :title="title">
    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
      <div
        class="h-full rounded-full transition-all"
        :class="CORES_BARRA[variant] ?? CORES_BARRA.neutral"
        :style="{ width: `${percentualClamp}%` }"
      ></div>
    </div>
    <p class="mt-1 text-[11px] font-medium leading-none" :class="CORES_TEXTO[variant] ?? CORES_TEXTO.neutral">
      {{ rotulo }}
    </p>
  </div>
</template>

<script setup>
/**
 * Barra horizontal de progresso generica: percentual + rotulo, em 4 variantes
 * de cor. Extraida de VistoriaProgressoBar (Tdap/Frota) para o mesmo desenho
 * servir a coluna de execucao de viagens em Cronogramas -- o calculo do
 * percentual e do rotulo e responsabilidade de quem chama, este componente so
 * desenha.
 */
import { computed } from 'vue';

const props = defineProps({
  /** 0-100. Fora da faixa e ajustado (clamp) para a barra nunca estourar. */
  percentual: { type: Number, default: 0 },
  rotulo: { type: String, default: '' },
  /** success | warning | danger | neutral */
  variant: { type: String, default: 'neutral' },
  title: { type: String, default: '' },
});

const percentualClamp = computed(() => Math.min(Math.max(props.percentual, 0), 100));

// Classes por extenso: string dinamica (`bg-${cor}-500`) some no purge do
// Tailwind. Mesma razao documentada em VistoriaSituacaoBadge.vue.
const CORES_BARRA = {
  success: 'bg-emerald-500',
  warning: 'bg-amber-500',
  danger: 'bg-red-500',
  neutral: 'bg-slate-300 dark:bg-slate-600',
};

const CORES_TEXTO = {
  success: 'text-emerald-600 dark:text-emerald-400',
  warning: 'text-amber-600 dark:text-amber-400',
  danger: 'text-red-600 dark:text-red-400',
  neutral: 'text-slate-400 dark:text-slate-500',
};
</script>
