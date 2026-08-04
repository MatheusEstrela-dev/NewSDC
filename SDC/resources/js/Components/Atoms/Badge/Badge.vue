<template>
  <span :class="badgeClasses">
    <slot />
  </span>
</template>

<script setup>
/**
 * Badge: fonte unica da aparencia de pill do sistema.
 *
 * A receita e a que o modulo Decretacoes estabeleceu e que 10 badges de modulo ja
 * repetiam a mao:
 *
 *   light:  bg-{cor}-100  text-{cor}-700  border border-{cor}-300
 *   dark:   bg-{cor}-500/20  text-{cor}-300  border-{cor}-500/30
 *
 * A borda no light e o que da o contorno definido da pill; sem ela o badge lia
 * como preenchimento chapado e destoava do resto do sistema (era o caso do RAT,
 * que consome este atomo).
 *
 * Duas formas de escolher a cor:
 *
 * - variant: semantica (success, warning, danger, info, default, neutral). Use
 *   quando o badge representa ESTADO. E o caminho preferido.
 * - cor: cor explicita da paleta. Use quando o badge representa uma CATEGORIA cujo
 *   significado nao e estado (Municipal azul, Estadual roxo, tipo de cisterna...).
 *   Consolidar essas em semanticas mudaria o significado, nao so a aparencia.
 *
 * Classes sempre estaticas, nunca interpoladas: o Tailwind varre literais no
 * codigo-fonte, e `bg-${cor}-100` nao entraria no CSS final.
 */
import { computed } from 'vue';

const CORES = {
  slate: 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-500/20 dark:text-slate-300 dark:border-slate-500/30',
  // Cinza mais forte, para estado encerrado sem desfecho (arquivado) nao se
  // confundir com estado apenas neutro (rascunho).
  'slate-forte': 'bg-slate-200 text-slate-800 border-slate-400 dark:bg-slate-600/30 dark:text-slate-300 dark:border-slate-500/40',
  blue: 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-500/20 dark:text-blue-300 dark:border-blue-500/30',
  sky: 'bg-sky-100 text-sky-700 border-sky-300 dark:bg-sky-500/20 dark:text-sky-300 dark:border-sky-500/30',
  cyan: 'bg-cyan-100 text-cyan-700 border-cyan-300 dark:bg-cyan-500/20 dark:text-cyan-300 dark:border-cyan-500/30',
  indigo: 'bg-indigo-100 text-indigo-700 border-indigo-300 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30',
  emerald: 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30',
  green: 'bg-green-100 text-green-700 border-green-300 dark:bg-green-500/20 dark:text-green-300 dark:border-green-500/30',
  teal: 'bg-teal-100 text-teal-700 border-teal-300 dark:bg-teal-500/20 dark:text-teal-300 dark:border-teal-500/30',
  lime: 'bg-lime-100 text-lime-700 border-lime-300 dark:bg-lime-500/20 dark:text-lime-300 dark:border-lime-500/30',
  amber: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/30',
  yellow: 'bg-yellow-100 text-yellow-800 border-yellow-300 dark:bg-yellow-500/20 dark:text-yellow-300 dark:border-yellow-500/30',
  orange: 'bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-500/20 dark:text-orange-300 dark:border-orange-500/30',
  red: 'bg-red-100 text-red-700 border-red-300 dark:bg-red-500/20 dark:text-red-300 dark:border-red-500/30',
  rose: 'bg-rose-100 text-rose-700 border-rose-300 dark:bg-rose-500/20 dark:text-rose-300 dark:border-rose-500/30',
  purple: 'bg-purple-100 text-purple-700 border-purple-300 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30',
  violet: 'bg-violet-100 text-violet-700 border-violet-300 dark:bg-violet-500/20 dark:text-violet-300 dark:border-violet-500/30',
};

/**
 * Semantica de cor comum aos modulos:
 *   success = concluido, ativo, vigente
 *   warning = em andamento, aguardando, em analise
 *   danger  = vencido, inativo, negado
 *   info    = informativo, sem juizo de estado
 *   default = neutro, ainda nao iniciado
 *   neutral = encerrado sem desfecho
 */
const SEMANTICA = {
  info: 'cyan',
  success: 'emerald',
  warning: 'amber',
  danger: 'red',
  default: 'slate',
  neutral: 'slate-forte',
};

// Listas literais nos validators de proposito: defineProps e icado para fora do
// escopo de setup pelo compilador, entao nao pode referenciar CORES/SEMANTICA.
const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (v) => ['info', 'success', 'warning', 'danger', 'default', 'neutral'].includes(v),
  },

  /** Cor explicita da paleta; tem prioridade sobre variant. */
  cor: {
    type: String,
    default: null,
    validator: (v) => v === null || [
      'slate', 'slate-forte', 'blue', 'sky', 'cyan', 'indigo',
      'emerald', 'green', 'teal', 'lime',
      'amber', 'yellow', 'orange', 'red', 'rose', 'purple', 'violet',
    ].includes(v),
  },

  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'pill'].includes(v),
  },

  rounded: {
    type: Boolean,
    default: true,
  },

  /**
   * Desliga a borda. Existe para casos sobre fundo colorido, onde o contorno
   * competiria com o container; o padrao e sempre COM borda.
   */
  semBorda: {
    type: Boolean,
    default: false,
  },
});

const TAMANHOS = {
  sm: 'px-2 py-0.5 text-xs',
  md: 'px-2 py-0.5 sm:px-2.5 sm:py-1 text-xs',
  lg: 'px-2.5 py-1 sm:px-3 sm:py-1.5 text-xs sm:text-sm',

  // Dimensao que os 10 badges de modulo (Compdec, Decretacoes, PAE) ja usavam
  // quando escreviam a receita a mao. Existe para a consolidacao deles nao alterar
  // espacamento nem peso de fonte -- refatorar nao deveria mexer na aparencia.
  // O font-semibold vem junto de proposito: e parte do mesmo conjunto herdado.
  pill: 'px-2.5 py-1 sm:px-3 sm:py-1.5 text-xs !font-semibold',
};

const badgeClasses = computed(() => {
  const cor = props.cor ?? SEMANTICA[props.variant] ?? 'slate';

  return [
    'inline-flex items-center font-medium',
    props.semBorda ? '' : 'border',
    CORES[cor],
    TAMANHOS[props.size],
    props.rounded ? 'rounded-full' : 'rounded',
  ].filter(Boolean).join(' ');
});
</script>
