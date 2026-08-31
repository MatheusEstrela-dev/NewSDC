<template>
  <div :class="cardClasses" @click="clickable && $emit('click')">
    <!-- pb reserva a faixa do bubble ancorado no canto inferior direito; sem
         ela, a ultima linha da legenda passa por baixo dele. Vai AQUI, no
         wrapper interno, e nao no card: no card, `pb-*` disputaria com o
         `md:py-4` da propria classe base, e a media query venceria no desktop --
         o espaco existiria no mobile e desapareceria justo onde a legenda e mais
         larga. -->
    <div
      class="flex items-start justify-between gap-2 sm:gap-4"
      :class="retratil && temDetalhe ? 'pb-6' : ''"
    >
      <div class="min-w-0 flex-1">
        <!-- Titulo + nota na mesma linha. Ancorar a nota aqui, e nao no rodape,
             mantem a posicao identica nos cinco cards, independente de o card
             ter rateio, barra de composicao ou nenhum dos dois. -->
        <div class="mb-0.5 sm:mb-1 flex items-center gap-1.5">
          <Text size="sm" color="muted" weight="medium" class="text-xs sm:text-sm leading-tight">
            {{ title }}
          </Text>

          <span v-if="nota" class="group/nota static lg:relative inline-flex shrink-0">
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
                 primeiro fica sob a sidebar.

                 Esse lado vale de `lg` para cima -- o MESMO breakpoint em que o
                 StatCardsGrid sai de 2 colunas. Abaixo disso ele nao serve:
                 `notaAlign` e escolhido para a fileira de 5 colunas do desktop, e
                 a grade reflui para 2 colunas -- o card que era o ultimo da
                 direita passa a ser o da esquerda, e o `right-0` jogava o bubble
                 para fora da tela (medido em 599px: caixa em -125..131, 125px
                 comidos pelo `overflow-x:clip` do <main>; e 376..632 no card da
                 coluna direita, 33px para fora). Em tela pequena, entao, o
                 tooltip para de se ancorar no icone (alvo de 20px para um bubble
                 de 256px) e passa a ocupar a largura do card -- o bloco de
                 referencia vira o proprio card, que ja e `relative`. Ancorar na
                 linha do titulo nao bastava: com 3 colunas em 768px a coluna de
                 texto tem ~136px, e o texto da nota pintava para fora da bolha. -->
            <span
              role="tooltip"
              :class="[
                'pointer-events-none absolute top-full z-30 mt-1 left-0 right-0 w-auto lg:w-64 rounded-md border border-cyan-200 bg-cyan-50 px-3 py-2 text-left text-sm leading-relaxed text-cyan-900 opacity-0 shadow-lg transition-opacity duration-150 group-hover/nota:opacity-100 group-focus-within/nota:opacity-100 dark:border-cyan-500/30 dark:bg-slate-800 dark:text-cyan-100',
                notaAlign === 'right' ? 'lg:left-auto lg:right-0' : 'lg:right-auto lg:left-0',
              ]"
            >
              {{ nota }}
            </span>
          </span>
        </div>
        <Heading :level="2" class="mb-0 text-xl sm:text-2xl md:text-3xl">
          {{ formattedValue }}
        </Heading>

        <!-- Rateio (por situacao de anormalidade em Decretacoes, por etapa de
             fiscalizacao na Cisterna) + leitura em grao municipio. A legenda
             cobre rotulo e numero: o title fica no container do item, nao so no
             rotulo. Item com `filtro` vira <button> de verdade, para ganhar
             teclado e anel de foco sem reimplementar nada. -->
        <div v-if="showBreakdown && detalheVisivel" class="breakdown mt-2 pt-2 border-t border-slate-200 dark:border-slate-700/50 flex flex-wrap gap-x-4 gap-y-1">
          <component
            v-for="item in itensRateio"
            :key="item.rotulo"
            :is="item.filtro !== undefined ? 'button' : 'div'"
            :type="item.filtro !== undefined ? 'button' : undefined"
            :class="[
              'breakdown-item flex flex-col items-start text-left',
              item.filtro !== undefined
                ? 'cursor-pointer rounded transition-opacity hover:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400'
                : 'cursor-help',
            ]"
            :title="item.dica"
            @click="clicarParte($event, item.filtro)"
          >
            <span class="text-[10px] sm:text-xs font-medium text-slate-500 uppercase">{{ item.rotulo }}</span>
            <span :class="['text-sm sm:text-base font-bold', item.valor > 0 ? 'text-cyan-400' : 'text-slate-400']">
              {{ formatNumber(item.valor) }}
            </span>
          </component>

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
        <div v-if="composicao && composicao.base > 0 && detalheVisivel" class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700/50">
          <div class="flex h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700/50">
            <div
              v-for="parte in composicao.partes"
              :key="parte.rotulo"
              :class="parte.classe"
              :style="{
                width: percentual(parte.valor) + '%',
                minWidth: Number(parte.valor) > 0 ? '2px' : '0',
              }"
              :title="`${parte.rotulo}: ${formatNumber(parte.valor)} de ${formatNumber(composicao.base)}`"
            ></div>
          </div>
          <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
            <component
              v-for="parte in composicao.partes"
              :key="parte.rotulo"
              :is="parte.filtro !== undefined ? 'button' : 'div'"
              :type="parte.filtro !== undefined ? 'button' : undefined"
              :class="[
                'flex flex-col items-start text-left',
                parte.filtro !== undefined
                  ? 'cursor-pointer rounded transition-opacity hover:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400'
                  : 'cursor-help',
              ]"
              :title="`${parte.rotulo}: ${formatNumber(parte.valor)} de ${formatNumber(composicao.base)}`"
              @click="clicarParte($event, parte.filtro)"
            >
              <span class="text-[10px] sm:text-xs font-medium text-slate-500 uppercase flex items-center gap-1">
                <span :class="['inline-block h-1.5 w-1.5 rounded-full shrink-0', parte.classe]"></span>
                {{ parte.rotulo }}
              </span>
              <span class="text-sm sm:text-base font-bold text-slate-700 dark:text-slate-200">
                {{ percentualFormatado(parte.valor) }}
              </span>
            </component>
          </div>
        </div>

      </div>

      <div v-if="icon" :class="iconContainerClasses">
        <component :is="icon" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6" />
      </div>
    </div>

    <!-- Retrator do DETALHE. Fica ANCORADO no canto inferior direito do card, e
         nao no fim da coluna de texto: assim a seta cai no mesmo eixo vertical do
         icone e, sobretudo, no MESMO ponto com o detalhe aberto ou fechado --
         posicao no fluxo mudava de lugar conforme a altura do conteudo.
         Bubble QUADRADO (rounded-md/lg, igual ao bloco do icone) e recuado
         exatamente como o padding horizontal do card (right-3/4/5), para a
         beirada dele alinhar com a beirada do bloco do icone logo acima. Com
         right-2 fixo, ele ficava 8px fora do alinhamento em md+.
         O espaco dele e reservado por pb no wrapper interno, senao a legenda
         passaria por baixo.
         O estado vem do pai (prop `detalhe`), e nao e local: uma seta recolhe o
         detalhe de TODOS os cards ao mesmo tempo. -->
    <button
      v-if="retratil && temDetalhe"
      type="button"
      class="absolute bottom-3 right-3 sm:bottom-4 sm:right-4 md:right-5 inline-flex items-center justify-center rounded-md p-1 sm:rounded-lg bg-slate-100 text-slate-500 ring-1 ring-slate-200 transition-colors hover:bg-slate-200 hover:text-cyan-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700 dark:hover:bg-slate-700 dark:hover:text-cyan-300"
      :aria-expanded="detalhe"
      :title="detalhe ? 'Recolher detalhe de todos os cards' : 'Mostrar detalhe de todos os cards'"
      @click="alternarDetalhe"
    >
      <ChevronUpIcon
        class="h-3.5 w-3.5 transition-transform duration-300"
        :class="detalhe ? '' : 'rotate-180'"
      />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Heading from '../../Atoms/Typography/Heading.vue';
import Text from '../../Atoms/Typography/Text.vue';
// Solid da lib do projeto: preenchido, ele se enxerga nesse tamanho, enquanto
// o outline (stroke 1.5) passava despercebido no rodape do card.
import { ChevronUpIcon, InformationCircleIcon } from '@heroicons/vue/24/solid';

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
  /**
   * Rateio generico, no lugar do trio ECP/SE/N1: cada item e
   * { rotulo, valor, dica?, filtro? }. Existe porque o rateio nem sempre e por
   * situacao de anormalidade -- na Cisterna sao as etapas de fiscalizacao
   * (fornecedor, COMPDEC, CEDEC), que sao subconjuntos ANINHADOS de uma mesma
   * base e por isso nao cabem na barra de composicao: empilhadas, 791+680+658
   * pintaria 2.129 sobre uma base de 791.
   *
   * Quando ausente, o rateio e montado de ecp/se/n1 e o comportamento e o
   * mesmo de antes -- Decretacoes, Estoque e Inventario nao mudam.
   *
   * `filtro` e opcional: com ele o item vira atalho de filtro e emite
   * `filtrar-parte`; sem ele, segue informativo. Prometer clique e nao filtrar
   * nada e pior que nao oferecer.
   */
  itens: {
    type: Array,
    default: null,
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

  /**
   * Liga a seta de recolher o detalhe dentro do card. Desligado por padrao para
   * que nenhum modulo mude de comportamento sem pedir.
   */
  retratil: {
    type: Boolean,
    default: false,
  },

  /**
   * Detalhe aberto. Controlado pelo PAI de proposito: o pai mantem um unico
   * estado para a fileira toda, entao a seta de qualquer card abre e fecha o
   * detalhe de todos ao mesmo tempo.
   */
  detalhe: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['click', 'filtrar-parte', 'alternar-detalhe']);

function formatNumber(num) {
  if (num === null || num === undefined) return '--';
  return num.toLocaleString('pt-BR');
}

// Declarado aqui, e nao no fim do arquivo, porque itensRateio o usa: a ordem
// evita depender de avaliacao tardia para escapar do TDZ do const.
const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('pt-BR');
  }
  return props.value;
});

/**
 * Sem `itens`, remonta o rateio historico de ECP/SE/N1 com os mesmos rotulos e
 * as mesmas dicas de antes, para que os modulos que passam ecp/se/n1 continuem
 * pixel-identicos.
 */
const itensRateio = computed(() => {
  if (Array.isArray(props.itens)) {
    return props.itens;
  }

  return [
    {
      rotulo: 'ECP',
      valor: props.ecp,
      dica: `Estado de Calamidade Publica: ${formatNumber(props.ecp)} de ${formattedValue.value}`,
    },
    {
      rotulo: 'SE',
      valor: props.se,
      dica: `Situacao de Emergencia: ${formatNumber(props.se)} de ${formattedValue.value}`,
    },
    {
      rotulo: 'N1',
      valor: props.n1,
      dica: `Nivel 1: ${formatNumber(props.n1)} de ${formattedValue.value}`,
    },
  ];
});

/**
 * stopPropagation somente quando a parte e clicavel: parte informativa dentro
 * de card clicavel tem que deixar o clique subir para o card, senao o usuario
 * clica na legenda e nada acontece.
 */
function clicarParte(evento, filtro) {
  if (filtro === undefined) {
    return;
  }

  evento.stopPropagation();
  emit('filtrar-parte', filtro);
}

/** Sem detalhe nenhum, a seta nao aparece: nao ha o que recolher. */
const temDetalhe = computed(
  () => (props.showBreakdown && itensRateio.value.length > 0)
    || (props.composicao !== null && Number(props.composicao?.base ?? 0) > 0)
);

const detalheVisivel = computed(() => ! props.retratil || props.detalhe);

function alternarDetalhe(evento) {
  // Sem stopPropagation o clique subiria para o card e aplicaria o filtro: o
  // usuario queria esconder o detalhe e ganharia uma listagem filtrada.
  evento.stopPropagation();
  emit('alternar-detalhe');
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
  // min-w-0: item de grid tem min-width:auto por padrao, ou seja nao encolhe
  // abaixo da largura MINIMA do proprio conteudo. Com a barra de composicao e a
  // legenda ("PROCESSAMENTO", "FORA DA LISTA"), esse minimo passou a ser maior
  // que a trilha do grid em 5 colunas: a fileira transbordava o container e o
  // ultimo card aparecia cortado na beirada, principalmente com zoom, que reduz
  // a largura em pixels CSS sem reduzir o texto na mesma proporcao. Com min-w-0
  // o card encolhe e a legenda quebra linha, em vez de vazar.
  const base =
    'relative min-w-0 hover:z-50 focus-within:z-50 rounded-lg sm:rounded-xl border backdrop-blur-sm px-3 py-3 sm:px-4 sm:py-4 md:px-5 md:py-4 lg:px-6 transition-all duration-300 hover:shadow-lg hover:scale-[1.02] active:scale-[0.98] touch-manipulation bg-white dark:bg-slate-900/60 hover:bg-slate-50 dark:hover:bg-slate-900/80';
  const cursor = props.clickable ? 'cursor-pointer' : '';
  return [base, variantBorderClasses[props.variant], cursor].filter(Boolean).join(' ');
});

const iconContainerClasses = computed(() => {
  return `p-1.5 sm:p-2 md:p-3 rounded-md sm:rounded-lg ${variantAccentClasses[props.variant]}`;
});
</script>
