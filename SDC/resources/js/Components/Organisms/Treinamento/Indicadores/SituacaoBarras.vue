<template>
  <div class="min-w-0">
    <div
      class="flex w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
      :class="fina ? 'h-1.5' : 'h-2.5'"
      role="img"
      :aria-label="descricao"
      :title="descricao"
    >
      <span
        v-for="segmento in segmentos"
        :key="segmento.chave"
        class="h-full"
        :style="{ width: `${segmento.pct}%`, backgroundColor: segmento.cor }"
      ></span>
    </div>

    <ul v-if="legenda" class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] text-slate-600 dark:text-slate-300">
      <li v-for="segmento in segmentos" :key="segmento.chave" class="inline-flex items-center gap-1">
        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: segmento.cor }"></span>
        <span class="tabular-nums font-semibold">{{ formatarNumero(segmento.valor) }}</span>
        <span>{{ segmento.nome.toLowerCase() }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
/**
 * Barra empilhada concluidos / em andamento / desistentes de uma linha do
 * servidor (porCurso, detalheMes e seus cursos: todos trazem os tres campos).
 * Fonte unica do desenho de situacao nos cards de curso e no detalhe do mes.
 */
import { computed } from 'vue';
import { SITUACOES, formatarNumero, percentual } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  /** Objeto com concluidos, em_andamento e desistentes. */
  linha: { type: Object, required: true },
  /** Contagens por situacao abaixo da barra. */
  legenda: { type: Boolean, default: false },
  fina: { type: Boolean, default: false },
});

const total = computed(() => SITUACOES.reduce((soma, s) => soma + Number(props.linha[s.chave] ?? 0), 0));

const segmentos = computed(() => SITUACOES.map((s) => {
  const valor = Number(props.linha[s.chave] ?? 0);
  return { ...s, valor, pct: percentual(valor, total.value) };
}));

const descricao = computed(() => segmentos.value
  .map((s) => `${s.nome}: ${formatarNumero(s.valor)} (${s.pct.toLocaleString('pt-BR')}%)`)
  .join(' · '));
</script>
