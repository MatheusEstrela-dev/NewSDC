<template>
  <TdapPainel rotulo="Perfil" titulo="Perfil do público" subtitulo="Sexo e faixa etária informados na inscrição">
    <ListEmptyState
      v-if="totalSexo === 0"
      :icon="UsersGroupIcon"
      title="Sem participantes no recorte"
      helper="Ajuste os filtros para ver o perfil do público."
    />
    <div v-else class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-x md:divide-y-0 dark:divide-slate-700/40">
      <section class="min-w-0 px-5 pt-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sexo</p>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ formatarNumero(totalSexo) }} participantes</p>
        <div class="h-64 min-w-0 py-2">
          <LazyChart type="bar" height="100%" :options="opcoesSexo" :series="seriesSexo" />
        </div>
      </section>

      <section class="min-w-0 px-5 pt-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Faixa etária</p>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Idade na data da inscrição</p>
        <div class="h-64 min-w-0 py-2">
          <LazyChart type="bar" height="100%" :options="opcoesFaixa" :series="seriesFaixa" />
        </div>
      </section>
    </div>
  </TdapPainel>
</template>

<script setup>
/**
 * Perfil do publico em barras, lado a lado: sexo (props.porSexo, cinco
 * categorias, barras horizontais) e faixa etaria (props.porFaixaEtaria, ja na
 * ordem das faixas, "Nao informado" por ultimo). Rotulo de cada barra traz
 * quantidade e percentual; os dois graficos seguem os filtros do painel.
 */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import UsersGroupIcon from '@/Components/Icons/UsersGroupIcon.vue';
import {
  COR_INSTITUCIONAL, COR_NAO_INFORMADO, SEXOS, formatarDecimal, formatarNumero, percentual, useTemaGrafico,
} from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porSexo: { type: Object, default: () => ({ feminino: 0, masculino: 0, nao_binario: 0, outro: 0, nao_informado: 0 }) },
  porFaixaEtaria: { type: Array, default: () => [] },
});

const { tema } = useTemaGrafico();

const totalSexo = computed(() => SEXOS.reduce((soma, s) => soma + Number(props.porSexo[s.chave] ?? 0), 0));
const totalFaixa = computed(() => props.porFaixaEtaria.reduce((soma, f) => soma + Number(f.total ?? 0), 0));

const seriesSexo = computed(() => [{ name: 'Participantes', data: SEXOS.map((s) => Number(props.porSexo[s.chave] ?? 0)) }]);
const seriesFaixa = computed(() => [{ name: 'Participantes', data: props.porFaixaEtaria.map((f) => Number(f.total ?? 0)) }]);

/** "391 (32,4%)": quantidade e fatia do total do grafico. */
const rotuloComPercentual = (total) => (v) => `${formatarNumero(v)} (${formatarDecimal(percentual(v, total))}%)`;

/**
 * Rotulo DENTRO da barra horizontal: so quando a barra tem largura para ele.
 * Barra zerada ou curta fica sem rotulo (o numero segue no tooltip), em vez de
 * texto branco sobre o fundo claro ou estourando a barra.
 */
const rotuloSeCouber = (total, maximo) => (v) => (v > 0 && v >= maximo * 0.18 ? rotuloComPercentual(total)(v) : '');

/** Base comum das duas barras (DRY): tema, eixos e tooltip iguais. */
function opcoesDeBarras({ categorias, cores, total, maximo, horizontal }) {
  const eixoTexto = { style: { colors: tema.value.corTexto, fontSize: '11px' } };

  return {
    chart: { type: 'bar', toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
    colors: cores,
    plotOptions: { bar: { horizontal, distributed: true, borderRadius: 3, barHeight: '62%', columnWidth: '60%', dataLabels: { position: horizontal ? 'center' : 'top' } } },
    dataLabels: horizontal
      ? { enabled: true, formatter: rotuloSeCouber(total, maximo), style: { fontSize: '11px', fontWeight: 600, colors: ['#fff'] }, dropShadow: { enabled: false } }
      : { enabled: false },
    grid: { borderColor: tema.value.corGrade },
    xaxis: {
      categories: categorias,
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: horizontal
        ? { ...eixoTexto, formatter: (v) => formatarNumero(Math.round(v)) }
        : { ...eixoTexto, rotate: -45, hideOverlappingLabels: false, trim: true },
    },
    yaxis: horizontal
      ? { labels: { ...eixoTexto, maxWidth: 110 } }
      : { min: 0, forceNiceScale: true, labels: { ...eixoTexto, formatter: (v) => formatarNumero(Math.round(v)) } },
    tooltip: { theme: tema.value.tooltip, y: { formatter: rotuloComPercentual(total) } },
    legend: { show: false },
  };
}

const opcoesSexo = computed(() => opcoesDeBarras({
  categorias: SEXOS.map((s) => s.nome),
  cores: SEXOS.map((s) => s.cor),
  total: totalSexo.value,
  maximo: Math.max(0, ...seriesSexo.value[0].data),
  horizontal: true,
}));

// "Nao informado" em cinza: nao e uma faixa, e a falta do dado.
const opcoesFaixa = computed(() => opcoesDeBarras({
  categorias: props.porFaixaEtaria.map((f) => f.rotulo),
  cores: props.porFaixaEtaria.map((f) => (f.faixa === 'nao_informado' ? COR_NAO_INFORMADO : COR_INSTITUCIONAL)),
  total: totalFaixa.value,
  horizontal: false,
}));
</script>
