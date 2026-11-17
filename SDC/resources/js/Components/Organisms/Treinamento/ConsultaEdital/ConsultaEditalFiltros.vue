<template>
  <TdapPainel rotulo="Filtros" titulo="Quem tem o curso" subtitulo="Escolha um ou mais cursos do catálogo; concluído e aprovado conta como ter o curso">
    <form class="space-y-4 px-5 py-4" @submit.prevent="consultar">
      <div class="space-y-2">
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400" for="edital-curso">Cursos</label>
        <select
          id="edital-curso"
          class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
          :value="''"
          @change="adicionar($event)"
        >
          <option value="">Adicionar curso...</option>
          <option v-for="curso in cursosDisponiveis" :key="curso.id" :value="curso.id">{{ curso.nome }}</option>
        </select>

        <ul v-if="selecionados.length" class="flex flex-wrap gap-2" aria-label="Cursos escolhidos">
          <li
            v-for="curso in selecionados"
            :key="curso.id"
            class="inline-flex min-h-[44px] max-w-full items-center gap-2 rounded-full bg-blue-50 py-1 pl-3 pr-1 text-sm text-blue-800 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/25"
          >
            <span class="min-w-0 truncate">{{ curso.nome }}</span>
            <button
              type="button"
              class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full hover:bg-blue-100 dark:hover:bg-blue-500/20"
              :aria-label="`Remover ${curso.nome}`"
              @click="remover(curso.id)"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </li>
        </ul>
      </div>

      <fieldset class="space-y-1">
        <legend class="text-xs font-medium text-slate-500 dark:text-slate-400">Critério</legend>
        <div class="inline-flex w-full rounded-lg border border-slate-300 p-0.5 dark:border-slate-600 sm:w-auto" role="radiogroup">
          <button
            v-for="m in MODOS"
            :key="m.valor"
            type="button"
            role="radio"
            :aria-checked="rascunho.modo === m.valor"
            class="min-h-[44px] flex-1 rounded-md px-4 text-sm font-medium transition-colors sm:flex-none"
            :class="rascunho.modo === m.valor
              ? 'bg-blue-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
            @click="rascunho.modo = m.valor"
          >
            {{ m.rotulo }}
          </button>
        </div>
      </fieldset>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label class="flex min-w-0 flex-col gap-1">
          <span class="text-xs font-medium text-slate-500 dark:text-slate-400">REDEC</span>
          <select v-model="rascunho.redec_id" class="h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
            <option value="">Todas as REDECs</option>
            <option v-for="r in opcoes.redecs" :key="r.id" :value="String(r.id)">{{ r.nome }}</option>
          </select>
        </label>
        <label class="flex min-w-0 flex-col gap-1">
          <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Município da pessoa</span>
          <select v-model="rascunho.municipio_id" class="h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
            <option value="">Todos os municípios</option>
            <option v-for="m in opcoes.municipios" :key="m.id" :value="String(m.id)">{{ m.nome }}</option>
          </select>
        </label>
      </div>

      <div class="flex justify-end">
        <button
          type="submit"
          class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white transition-colors hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
          :disabled="selecionados.length === 0 || carregando"
        >
          {{ carregando ? 'Consultando...' : 'Consultar' }}
        </button>
      </div>
    </form>
  </TdapPainel>
</template>

<script setup>
/**
 * Formulario da consulta para edital. Mantem um rascunho local e so sobe os
 * filtros ao clicar em Consultar; quem faz a requisicao e a pagina.
 */
import { computed, reactive } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';

const MODOS = [
  { valor: 'todos', rotulo: 'Todos os cursos' },
  { valor: 'qualquer', rotulo: 'Qualquer um' },
];

const props = defineProps({
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
  carregando: { type: Boolean, default: false },
});

const emit = defineEmits(['consultar']);

const rascunho = reactive({
  cursos: [...(props.filtros.cursos ?? [])],
  modo: props.filtros.modo ?? 'todos',
  redec_id: props.filtros.redec_id ? String(props.filtros.redec_id) : '',
  municipio_id: props.filtros.municipio_id ? String(props.filtros.municipio_id) : '',
});

const cursosPorId = computed(() => new Map((props.opcoes.cursos ?? []).map((c) => [c.id, c])));
const selecionados = computed(() => rascunho.cursos.map((id) => cursosPorId.value.get(id)).filter(Boolean));
const cursosDisponiveis = computed(() => (props.opcoes.cursos ?? []).filter((c) => !rascunho.cursos.includes(c.id)));

function adicionar(evento) {
  const id = Number(evento.target.value);
  evento.target.value = '';
  if (id && !rascunho.cursos.includes(id)) rascunho.cursos.push(id);
}

const remover = (id) => { rascunho.cursos = rascunho.cursos.filter((c) => c !== id); };

function consultar() {
  if (rascunho.cursos.length === 0) return;
  emit('consultar', {
    cursos: [...rascunho.cursos],
    modo: rascunho.modo,
    redec_id: rascunho.redec_id,
    municipio_id: rascunho.municipio_id,
  });
}
</script>
