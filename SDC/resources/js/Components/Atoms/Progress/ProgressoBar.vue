<template>
  <div class="w-full max-w-[160px]" :title="title">
    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
      <div
        class="h-full rounded-full transition-all"
        :class="CORES_BARRA[estadoVisual]"
        :style="{ width: `${percentualClamp}%` }"
      ></div>
    </div>
    <p class="mt-1 text-xs font-medium leading-tight" :class="CORES_TEXTO[estadoVisual]">
      {{ rotulo }}
    </p>
  </div>
</template>

<script setup>
/**
 * Barra horizontal de progresso generica: percentual + rotulo. O calculo do
 * percentual e do rotulo e de quem chama; a COR e decidida aqui, numa regra so
 * para o modulo inteiro:
 *
 *   sem dados  -> cinza
 *   concluido  -> verde     (vence o atraso: terminou, ainda que depois do prazo)
 *   atrasado   -> vermelho  (passou do prazo sem concluir)
 *   andamento  -> azul
 */
import { computed } from 'vue';

const props = defineProps({
  /** 0-100. Fora da faixa e ajustado (clamp) para a barra nunca estourar. */
  percentual: { type: Number, default: 0 },
  rotulo: { type: String, default: '' },
  title: { type: String, default: '' },
  concluido: { type: Boolean, default: false },
  atrasado: { type: Boolean, default: false },
  semDados: { type: Boolean, default: false },
});

const percentualClamp = computed(() => Math.min(Math.max(props.percentual, 0), 100));

const estadoVisual = computed(() => {
  if (props.semDados) return 'sem_dados';
  if (props.concluido) return 'concluido';
  if (props.atrasado) return 'atrasado';

  return 'andamento';
});

// Classes por extenso: string dinamica (`bg-${cor}-500`) some no purge do
// Tailwind. Mesma razao documentada em VistoriaSituacaoBadge.vue.
const CORES_BARRA = {
  andamento: 'bg-blue-500',
  concluido: 'bg-emerald-500',
  atrasado: 'bg-red-500',
  sem_dados: 'bg-slate-300 dark:bg-slate-600',
};

const CORES_TEXTO = {
  andamento: 'text-blue-600 dark:text-blue-400',
  concluido: 'text-emerald-600 dark:text-emerald-400',
  atrasado: 'text-red-600 dark:text-red-400',
  sem_dados: 'text-slate-400 dark:text-slate-500',
};
</script>
