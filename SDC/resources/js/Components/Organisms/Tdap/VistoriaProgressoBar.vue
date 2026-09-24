<template>
  <div class="w-full max-w-[160px]" :title="titulo">
    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
      <div
        class="h-full rounded-full transition-all"
        :class="corDaBarra"
        :style="{ width: `${percentual}%` }"
      ></div>
    </div>
    <p class="mt-1 text-[11px] font-medium leading-none" :class="corDoTexto">
      {{ rotulo }}
    </p>
  </div>
</template>

<script setup>
/**
 * Progresso da vigencia de 12 meses, em barra: o quanto ja foi consumido do
 * prazo, e nao so quanto falta. "90 dias vencida" na coluna anterior nao dizia
 * se isso e muito ou pouco perto do fim do ciclo -- a barra cheia sim.
 *
 * `diasRestantes` e o mesmo contrato assinado do resto do modulo
 * (VigenciaVistoria::diasRestantes): negativo = vencida, null = sem vistoria.
 * O ciclo e sempre 12 meses (~365d): sem a data exata da proxima vigencia no
 * payload, 365 e aproximacao suficiente para uma barra visual -- a diferenca
 * de 1 dia bissexto nao muda um pixel.
 */
import { computed } from 'vue';

const VIGENCIA_DIAS = 365;

const props = defineProps({
  /** Assinado: negativo = vencida, 0 = vence hoje, null = sem vistoria. */
  diasRestantes: { type: Number, default: null },
});

const semDados = computed(() => props.diasRestantes === null || props.diasRestantes === undefined);

// Quanto do ciclo de 12 meses ja passou, e nao quanto falta: e o que a barra
// mostra enchendo.
const percentual = computed(() => {
  if (semDados.value) return 0;

  const decorridos = VIGENCIA_DIAS - props.diasRestantes;

  return Math.round(Math.min(Math.max(decorridos, 0), VIGENCIA_DIAS) / VIGENCIA_DIAS * 100);
});

const corDaBarra = computed(() => {
  if (semDados.value) return 'bg-slate-300 dark:bg-slate-600';
  if (props.diasRestantes < 0) return 'bg-red-500';
  if (props.diasRestantes <= 30) return 'bg-amber-500';

  return 'bg-emerald-500';
});

const corDoTexto = computed(() => {
  if (semDados.value) return 'text-slate-400 dark:text-slate-500';
  if (props.diasRestantes < 0) return 'text-red-600 dark:text-red-400';
  if (props.diasRestantes <= 30) return 'text-amber-600 dark:text-amber-400';

  return 'text-emerald-600 dark:text-emerald-400';
});

const rotulo = computed(() => {
  if (semDados.value) return 'Sem vistoria';
  if (props.diasRestantes < 0) return `${percentual.value}% · vencida há ${Math.abs(props.diasRestantes)}d`;
  if (props.diasRestantes === 0) return `${percentual.value}% · vence hoje`;

  return `${percentual.value}% · ${props.diasRestantes}d restantes`;
});

const titulo = computed(() => (semDados.value
  ? 'Este caminhão nunca foi vistoriado'
  : `${percentual.value}% do ciclo de 12 meses decorrido`));
</script>
