<template>
  <section class="relative isolate overflow-hidden rounded-xl bg-gradient-to-br from-orange-700 via-orange-800 to-[#431407] p-5 text-white shadow-lg ring-1 ring-orange-500/20 dark:from-[#2a1206] dark:via-slate-900 dark:to-slate-950 dark:ring-orange-500/30">
    <!-- Brilho do canto: so decoracao, atras do conteudo. -->
    <div class="pointer-events-none absolute -right-16 -top-16 -z-10 h-48 w-48 rounded-full bg-orange-400/30 blur-3xl dark:bg-orange-500/20" aria-hidden="true" />
    <p class="text-xs font-semibold uppercase tracking-wider text-orange-100 dark:text-orange-400">Visão geral</p>

    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1">
      <SeletorCompacto
        rotulo="Curso"
        sobre-destaque
        :model-value="valor('curso_id')"
        :opcoes="opcoesCurso"
        @update:model-value="$emit('alterar', { curso_id: $event, treinamento_id: '' })"
      />
      <SeletorCompacto
        v-if="mostrarTurma"
        rotulo="Turma"
        sobre-destaque
        :model-value="valor('treinamento_id')"
        :opcoes="opcoesTurma"
        @update:model-value="$emit('alterar', { treinamento_id: $event })"
      />
    </div>

    <fieldset class="mt-3">
      <legend class="text-xs font-medium text-orange-100 dark:text-orange-300/90">Situação</legend>
      <div class="mt-1 flex flex-wrap gap-x-4">
        <label
          v-for="situacao in SITUACOES"
          :key="situacao.valor"
          class="inline-flex min-h-[40px] cursor-pointer items-center gap-2 text-sm"
          :class="{ 'cursor-not-allowed opacity-70': ultimaMarcada(situacao.valor) }"
        >
          <input
            type="checkbox"
            class="h-4 w-4 rounded border-white/60 bg-white/10 text-orange-500 focus:ring-orange-200 dark:border-orange-500/40 dark:bg-slate-900/60"
            :checked="marcadas.includes(situacao.valor)"
            :disabled="ultimaMarcada(situacao.valor)"
            @change="alternarSituacao(situacao.valor)"
          />
          {{ situacao.nome }}
        </label>
      </div>
    </fieldset>

    <div class="mt-4 border-t border-white/20 pt-4 dark:border-orange-500/20">
      <p class="text-5xl font-bold leading-none tabular-nums">{{ formatarNumero(kpis.total_participantes) }}</p>
      <p class="mt-1 text-sm text-orange-100 dark:text-orange-300/90">participantes</p>
    </div>

    <!-- Cada total de situacao e atalho de filtro: clicar deixa so ela; de novo, volta a todas. -->
    <ul class="mt-4 grid grid-cols-3 gap-2">
      <li v-for="item in totaisSituacao" :key="item.valor" class="min-w-0">
        <button
          type="button"
          class="block w-full min-w-0 rounded-lg px-2 py-1.5 text-left transition-colors hover:bg-white/10"
          :title="`Ver só ${item.nome.toLowerCase()}`"
          @click="filtrarSo(item.valor)"
        >
          <span class="flex items-center gap-1.5 text-xl font-semibold tabular-nums">
            <span class="h-2 w-2 shrink-0 rounded-full ring-1 ring-white/60" :style="{ backgroundColor: item.cor }"></span>
            {{ formatarNumero(item.total) }}
          </span>
          <span class="block text-[11px] leading-tight text-orange-100 dark:text-slate-400">{{ item.nome.toLowerCase() }}</span>
          <span v-if="item.valor === 'DESISTENTE'" class="mt-0.5 block text-[11px] font-semibold text-white dark:text-orange-300">
            {{ formatarDecimal(kpis.taxa_desistencia_pct) }}% de desistência
          </span>
        </button>
      </li>
    </ul>

    <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-white/20 pt-3 dark:border-orange-500/20">
      <div v-for="item in totaisAlcance" :key="item.rotulo" class="min-w-0 px-2">
        <dd class="text-lg font-semibold tabular-nums">{{ formatarNumero(item.valor) }}</dd>
        <dt class="text-[11px] leading-tight text-orange-100 dark:text-slate-400">{{ item.rotulo }}</dt>
      </div>
    </dl>
  </section>
</template>

<script setup>
/**
 * Card de destaque: os filtros de curso, turma e situacao e os totais do
 * recorte (props.kpis). Emite `alterar` com os filtros que mudam juntos (trocar
 * de curso zera a turma, que pertence ao curso anterior). Laranja da Defesa
 * Civil: no claro, gradiente a partir do orange-700 (texto branco passa AA); no
 * escuro, base quase preta com brilho e borda laranja.
 */
import { computed } from 'vue';
import SeletorCompacto from '@/Components/Organisms/Treinamento/Indicadores/SeletorCompacto.vue';
import { SITUACOES, formatarDecimal, formatarNumero, situacoesFiltradas } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  kpis: { type: Object, required: true },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
});

const emit = defineEmits(['alterar']);

const valor = (chave) => (props.filtros[chave] ? String(props.filtros[chave]) : '');

const opcoesCurso = computed(() => [
  { value: '', label: 'Todos os cursos' },
  ...(props.opcoes.cursos ?? []).map((c) => ({ value: String(c.id), label: c.nome })),
]);

// Turma so aparece com curso escolhido, ou quando a turma escolhida (pelo card
// de curso) nao tem curso: ai a lista e a das turmas avulsas.
const mostrarTurma = computed(() => Boolean(props.filtros.curso_id || props.filtros.treinamento_id));

const opcoesTurma = computed(() => {
  const curso = props.filtros.curso_id ? String(props.filtros.curso_id) : null;
  const escolhida = valor('treinamento_id');
  // A turma ja escolhida fica na lista mesmo fora do curso (link antigo na URL):
  // sem ela o select mostraria outra turma como se fosse a filtrada.
  const turmas = (props.opcoes.treinamentos ?? []).filter((t) => String(t.id) === escolhida || (curso
    ? String(t.curso_id) === curso
    : t.curso_id === null || t.curso_id === undefined));

  return [
    { value: '', label: 'Todas as turmas' },
    ...turmas.map((t) => ({ value: String(t.id), label: t.titulo })),
  ];
});

// Sem filtro = as tres marcadas.
const filtradas = computed(() => situacoesFiltradas(props.filtros.situacoes));
const marcadas = computed(() => (filtradas.value.length ? filtradas.value : SITUACOES.map((s) => s.valor)));

// Desmarcar a ultima deixaria o recorte vazio, que o servidor le como "todas".
const ultimaMarcada = (situacao) => marcadas.value.length === 1 && marcadas.value[0] === situacao;

const emitirSituacoes = (lista) => emit('alterar', { situacoes: situacoesFiltradas(lista) });

function alternarSituacao(situacao) {
  const lista = marcadas.value.includes(situacao)
    ? marcadas.value.filter((v) => v !== situacao)
    : [...marcadas.value, situacao];
  emitirSituacoes(lista);
}

function filtrarSo(situacao) {
  const jaSo = filtradas.value.length === 1 && filtradas.value[0] === situacao;
  emitirSituacoes(jaSo ? [] : [situacao]);
}

const totaisSituacao = computed(() => SITUACOES.map((s) => ({ ...s, total: props.kpis[s.chave] ?? 0 })));

const totaisAlcance = computed(() => [
  { rotulo: 'turmas', valor: props.kpis.total_capacitacoes },
  { rotulo: 'municípios', valor: props.kpis.municipios_atendidos },
  { rotulo: 'REDECs', valor: props.kpis.redecs_atendidas },
]);
</script>
