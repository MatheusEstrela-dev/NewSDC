<template>
  <span
    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
    :class="classes"
    :title="titulo"
  >
    {{ rotulo }}
  </span>
</template>

<script setup>
/**
 * Prazo de vigencia em forma de selo: "vence hoje", "vence em 12d", "vencido
 * ha 343d".
 *
 * O markup e as classes vinham escritos inline em Atas/Index.vue (L272-287) e
 * seriam copiados de novo na fila de viagens. Extrair evita a terceira copia --
 * e o dia em que uma tela diz "vence em 3 dias" e a outra diz "vencido".
 *
 * Recebe `dias_restantes` no mesmo contrato que o backend ja publica
 * (VigenciaAta::diasRestantes): assinado, com negativo = vencido, 0 = vence
 * hoje, null = sem data.
 */
import { computed } from 'vue';

const props = defineProps({
  /** Assinado: negativo = vencido, 0 = vence hoje, null = sem data final. */
  diasRestantes: { type: Number, default: null },
  /** Dentro da janela de alerta de 30 dias (vem pronto do backend). */
  proximaVencer: { type: Boolean, default: false },
});

// Mapa token -> classes por extenso, e nao `bg-${cor}-100`: string dinamica e
// removida pelo purge do Tailwind no build. Mesma razao documentada em
// Atas/Index.vue.
const CLASSES = {
  success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  danger: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  neutral: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
};

const semData = computed(() => props.diasRestantes === null || props.diasRestantes === undefined);

const classes = computed(() => {
  if (semData.value) return CLASSES.neutral;
  if (props.diasRestantes < 0) return CLASSES.danger;
  if (props.proximaVencer) return CLASSES.warning;

  return CLASSES.success;
});

const rotulo = computed(() => {
  if (semData.value) return 'sem prazo';
  if (props.diasRestantes < 0) return `vencido há ${Math.abs(props.diasRestantes)}d`;
  // "vence hoje" le melhor que "vence em 0d".
  if (props.diasRestantes === 0) return 'vence hoje';

  return `vence em ${props.diasRestantes}d`;
});

const titulo = computed(() => (semData.value
  ? 'Cronograma sem data final'
  : `Vigência do cronograma: ${props.diasRestantes} dia(s)`));
</script>
