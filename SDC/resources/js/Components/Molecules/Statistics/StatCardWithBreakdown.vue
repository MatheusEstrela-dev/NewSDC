<template>
  <div :class="cardClasses" @click="clickable && $emit('click')">
    <div class="flex items-start justify-between gap-2 sm:gap-4">
      <div class="min-w-0 flex-1">
        <!-- Titulo + nota na mesma linha. Ancorar a nota aqui, e nao no rodape,
             mantem a posicao identica nos cinco cards, independente de o card
             ter rateio, barra de composicao ou nenhum dos dois. -->
        <div class="mb-0.5 sm:mb-1 flex items-center gap-1.5">
          <Text size="sm" color="muted" weight="medium" class="text-xs sm:text-sm leading-tight">
            {{ title }}
          </Text>

          <span v-if="nota" class="group/nota relative inline-flex shrink-0">
            <button
              type="button"
              class="flex rounded-full text-cyan-600 transition-colors hover:text-cyan-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:text-cyan-400 dark:hover:text-cyan-300"
              :aria-label="nota"
              @click.stop
            >
              <InformationCircleIcon class="h-5 w-5" />
            </button>
            <!-- Abre para baixo: no topo do card, para cima sairia da tela.
                 O lado e explicito por card: nao existe valor unico que sirva
                 aos dois extremos da grade. Crescendo sempre para a direita, o
                 ultimo card estoura a viewport; sempre para a esquerda, o
                 primeiro fica sob a sidebar. -->
            <span
              role="tooltip"
              :class="[
                'pointer-events-none absolute top-full z-30 mt-1 w-64 rounded-md border border-cyan-200 bg-cyan-50 px-3 py-2 text-left text-sm leading-relaxed text-cyan-900 opacity-0 shadow-lg transition-opacity duration-150 group-hover/nota:opacity-100 group-focus-within/nota:opacity-100 dark:border-cyan-500/30 dark:bg-slate-800 dark:text-cyan-100',
                notaAlign === 'right' ? 'right-0' : 'left-0',
              ]"
            >
              {{ nota }}
            </span>
          </span>
        </div>
        <Heading :level="2" class="mb-0 text-xl sm:text-2xl md:text-3xl">
          {{ formattedValue }}
        </Heading>

        <!-- Breakdown ECP/SE/N1 (rateio por situacao de anormalidade) +
             leitura em grao municipio. A legenda cobre rotulo e numero:
             o title fica no container do item, nao so no rotulo. -->
        <div v-if="showBreakdown" class="breakdown mt-2 pt-2 border-t border-slate-200 dark:border-slate-700/50 flex gap-4">
          <div class="breakdown-item flex flex-col cursor-help" :title="`Estado de Calamidade Publica: ${formatNumber(ecp)} de ${formattedValue}`">
            <span class="text-[10px] sm:text-xs font-medium text-slate-500 uppercase">ECP</span>
            <span :class="['text-sm sm:text-base font-bold', ecp > 0 ? 'text-cyan-400' : 'text-slate-400']">
              {{ formatNumber(ecp) }}
            </span>
          </div>
          <div class="breakdown-item flex flex-col cursor-help" :title="`Situacao de Emergencia: ${formatNumber(se)} de ${formattedValue}`">
            <span class="text-[10px] sm:text-xs font-medium text-slate-500 uppercase">SE</span>
            <span :class="['text-sm sm:text-base font-bold', se > 0 ? 'text-cyan-400' : 'text-slate-400']">
              {{ formatNumber(se) }}
            </span>
          </div>
          <div class="breakdown-item flex flex-col cursor-help" :title="`Nivel 1: ${formatNumber(n1)} de ${formattedValue}`">
            <span class="text-[10px] sm:text-xs font-medium text-slate-500 uppercase">N1</span>
            <span :class="['text-sm sm:text-base font-bold', n1 > 0 ? 'text-cyan-400' : 'text-slate-400']">
              {{ formatNumber(n1) }}
            </span>
          </div>

          <!-- Grao municipio: eixo distinto do rateio ECP/SE/N1 (remede o
               total, nao o fatia). Divisoria e cor propria para nao ser lido
               como mais uma fatia. -->
          <div
            v-if="porMunicipio !== null"
            class="breakdown-item flex flex-col cursor-help ml-auto pl-3 sm:pl-4 border-l border-slate-200 dark:border-slate-700/50"
            :title="`Mesmo conjunto contado por municipio: ${formattedValue} processos abrangem ${formatNumber(porMunicipio)} municipios`"
          >
            <span class="text-[10px] sm:text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Municipio</span>
            <span :class="['text-sm sm:text-base font-bold', porMunicipio > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-slate-400']">
              {{ formatNumber(porMunicipio) }}
            </span>
          </div>

        </div>

        <!-- Composicao: ocupa o lugar do rateio nos cards que nao o exibem.
             Barra empilhada da o peso relativo, os itens dao o numero. -->
        <div v-if="composicao && composicao.base > 0" class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700/50">
          <div class="flex h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700/50">
            <div
              v-for="parte in composicao.partes"
              :key="parte.rotulo"
              :class="parte.classe"
              :style="{ width: percentual(parte.valor) + '%' }"
              :title="`${parte.rotulo}: ${formatNumber(parte.valor)} de ${formatNumber(composicao.base)}`"
            ></div>
          </div>
          <div class="mt-2 flex gap-4">
            <div
              v-for="parte in composicao.partes"
              :key="parte.rotulo"
              class="flex flex-col cursor-help"
              :title="`${parte.rotulo}: ${formatNumber(parte.valor)} de ${formatNumber(composicao.base)}`"
            >
              <span class="text-[10px] sm:text-xs font-medium text-slate-500 uppercase flex items-center gap-1">
                <span :class="['inline-block h-1.5 w-1.5 rounded-full', parte.classe]"></span>
                {{ parte.rotulo }}
              </span>
              <span class="text-sm sm:text-base font-bold text-slate-700 dark:text-slate-200">
                {{ percentualFormatado(parte.valor) }}
              </span>
            </div>
          </div>
        </div>

      </div>

      <div v-if="icon" :class="iconContainerClasses">
        <component :is="icon" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Heading from '../../Atoms/Typography/Heading.vue';
import Text from '../../Atoms/Typography/Text.vue';
// Solid da lib do projeto: preenchido, ele se enxerga nesse tamanho, enquanto
// o outline (stroke 1.5) passava despercebido no rodape do card.
import { InformationCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [Number, String],
    required: true,
  },
  ecp: {
    type: Number,
    default: 0,
  },
  se: {
    type: Number,
    default: 0,
  },
  n1: {
    type: Number,
    default: 0,
  },
  // null oculta o slot: usado por metricas que ja sao medidas em grao
  // municipio e portanto nao tem leitura secundaria.
  porMunicipio: {
    type: Number,
    default: null,
  },
  showBreakdown: {
    type: Boolean,
    default: true,
  },
  // Rateio proporcional exibido no lugar do breakdown, para cards cujo
  // rateio ECP/SE/N1 nao informa nada (Total e Registros).
  // Formato: { base: Number, partes: [{ rotulo, valor, classe }] }
  composicao: {
    type: Object,
    default: null,
  },
  // Texto curto que desfaz uma leitura errada do numero principal.
  nota: {
    type: String,
    default: '',
  },
  // Lado para o qual o tooltip da nota cresce. Use 'right' nos cards no fim da
  // grade, senao o balao sai da viewport.
  notaAlign: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'right'].includes(value),
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  variant: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'success', 'warning', 'danger'].includes(value),
  },
  clickable: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['click']);

function formatNumber(num) {
  if (num === null || num === undefined) return '--';
  return num.toLocaleString('pt-BR');
}

// Base zero devolve 0 para nao gerar NaN na largura da barra.
function percentual(valor) {
  const base = Number(props.composicao?.base || 0);
  if (!base) return 0;
  return (Number(valor || 0) / base) * 100;
}

function percentualFormatado(valor) {
  return `${percentual(valor).toFixed(1).replace('.', ',')}%`;
}

const variantAccentClasses = {
  info: 'bg-cyan-100 dark:bg-cyan-500/15 text-cyan-700 dark:text-cyan-300 ring-1 ring-cyan-300 dark:ring-cyan-500/25',
  success: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-300 dark:ring-emerald-500/25',
  warning: 'bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 ring-1 ring-amber-300 dark:ring-amber-500/25',
  danger: 'bg-red-100 dark:bg-red-500/15 text-red-700 dark:text-red-300 ring-1 ring-red-300 dark:ring-red-500/25',
};

const variantBorderClasses = {
  info: 'border-cyan-200 dark:border-cyan-500/25',
  success: 'border-emerald-200 dark:border-emerald-500/25',
  warning: 'border-amber-200 dark:border-amber-500/25',
  danger: 'border-red-200 dark:border-red-500/25',
};

const cardClasses = computed(() => {
  // relative + hover:z-50: o hover:scale cria contexto de empilhamento em cada
  // card, entao o tooltip da nota ficava preso na camada do proprio card e o
  // card seguinte no DOM pintava por cima dele. Elevar o card inteiro no hover
  // resolve sem precisar teletransportar o tooltip para fora.
  const base =
    'relative hover:z-50 focus-within:z-50 rounded-lg sm:rounded-xl border backdrop-blur-sm px-3 py-3 sm:px-4 sm:py-4 md:px-5 md:py-4 transition-all duration-300 hover:shadow-lg hover:scale-[1.02] active:scale-[0.98] touch-manipulation bg-white dark:bg-slate-900/60 hover:bg-slate-50 dark:hover:bg-slate-900/80';
  const cursor = props.clickable ? 'cursor-pointer' : '';
  return [base, variantBorderClasses[props.variant], cursor].filter(Boolean).join(' ');
});

const iconContainerClasses = computed(() => {
  return `p-1.5 sm:p-2 md:p-3 rounded-md sm:rounded-lg ${variantAccentClasses[props.variant]}`;
});

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('pt-BR');
  }
  return props.value;
});
</script>
