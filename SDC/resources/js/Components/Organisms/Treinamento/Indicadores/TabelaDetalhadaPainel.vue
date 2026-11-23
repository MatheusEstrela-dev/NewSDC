<template>
  <TdapPainel rotulo="Detalhamento" titulo="Capacitações realizadas" subtitulo="Uma linha por turma, com município, REDEC, participantes e situação">
    <template #acoes>
      <button
        v-show="estaExpandido"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
        :aria-expanded="filtrosAbertos"
        @click="filtrosAbertos = !filtrosAbertos"
      >
        <FunnelIcon class="h-4 w-4" />
        Filtros
        <span v-if="ativos > 0" class="rounded-full bg-blue-600 px-1.5 text-[10px] font-semibold text-white">{{ ativos }}</span>
      </button>
      <button
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-600 transition-colors hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
        :aria-expanded="estaExpandido"
        :aria-label="estaExpandido ? 'Recolher detalhamento' : 'Expandir detalhamento'"
        @click="alternar"
      >
        <ChevronDownIcon class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': estaExpandido }" />
      </button>
    </template>

    <p v-if="!estaExpandido" class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400">
      {{ formatarNumero(paginacao?.total ?? linhas.length) }} turma(s) no recorte. Expanda para ver a lista.
    </p>

    <div v-show="estaExpandido">

    <div v-show="filtrosAbertos" class="grid grid-cols-1 gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/40 sm:grid-cols-2">
      <SeletorCompacto rotulo="REDEC" :model-value="valor('redec_id')" :opcoes="opcoesRedec" @update:model-value="$emit('alterar', { chave: 'redec_id', valor: $event })" />
      <SeletorCompacto rotulo="Município" :model-value="valor('municipio_id')" :opcoes="opcoesMunicipio" @update:model-value="$emit('alterar', { chave: 'municipio_id', valor: $event })" />
      <div v-if="ativos > 0" class="flex justify-end sm:col-span-2">
        <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400" @click="$emit('limpar')">Limpar filtros</button>
      </div>
    </div>

    <ListEmptyState
      v-if="linhas.length === 0"
      :icon="DocumentTextIcon"
      title="Nenhuma capacitação no recorte"
      helper="Não há turmas com participante capacitado para os filtros escolhidos."
    />

    <div v-else :class="{ 'opacity-60 transition-opacity': carregando }">
      <ResponsiveTable
        :items="linhas"
        :mobile-fields="camposMobile"
        :get-item-title="(linha) => linha.treinamento"
        :get-item-subtitle="(linha) => linha.local"
        :get-item-key="(linha, indice) => indice"
      >
        <template #table>
          <div class="min-w-0 overflow-x-auto">
            <table class="w-full text-left text-xs lg:text-sm">
              <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                <tr>
                  <th class="px-4 py-2.5 font-semibold">Capacitação</th>
                  <th class="px-3 py-2.5 font-semibold">Município</th>
                  <th class="px-3 py-2.5 font-semibold">REDEC</th>
                  <th class="hidden px-3 py-2.5 font-semibold xl:table-cell">Mesorregião</th>
                  <th class="px-3 py-2.5 font-semibold">Período</th>
                  <th class="px-3 py-2.5 text-right font-semibold">Part.</th>
                  <th class="px-3 py-2.5 text-right font-semibold" title="Concluídos">Concl.</th>
                  <th class="px-3 py-2.5 text-right font-semibold" title="Em andamento">Em and.</th>
                  <th class="px-3 py-2.5 text-right font-semibold" title="Desistentes">Desist.</th>
                  <th class="hidden px-3 py-2.5 text-right font-semibold 2xl:table-cell">Masc.</th>
                  <th class="hidden px-4 py-2.5 text-right font-semibold 2xl:table-cell">Fem.</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700/40">
                <tr v-for="(linha, indice) in linhas" :key="indice" class="text-slate-700 dark:text-slate-200">
                  <td class="max-w-[16rem] px-4 py-2.5 font-medium text-slate-900 dark:text-slate-100">
                    <span class="line-clamp-2" :title="linha.treinamento">{{ linha.treinamento }}</span>
                  </td>
                  <td class="px-3 py-2.5">{{ linha.local }}</td>
                  <td class="px-3 py-2.5">{{ linha.redec }}</td>
                  <td class="hidden px-3 py-2.5 xl:table-cell">{{ linha.mesorregiao }}</td>
                  <td class="whitespace-nowrap px-3 py-2.5">{{ linha.periodo }}</td>
                  <td class="px-3 py-2.5 text-right font-semibold tabular-nums">{{ formatarNumero(linha.participantes) }}</td>
                  <td class="px-3 py-2.5 text-right tabular-nums">{{ formatarNumero(linha.concluidos) }}</td>
                  <td class="px-3 py-2.5 text-right tabular-nums">{{ formatarNumero(linha.em_andamento) }}</td>
                  <td class="px-3 py-2.5 text-right tabular-nums">{{ formatarNumero(linha.desistentes) }}</td>
                  <td class="hidden px-3 py-2.5 text-right tabular-nums 2xl:table-cell">{{ formatarNumero(linha.masculino) }}</td>
                  <td class="hidden px-4 py-2.5 text-right tabular-nums 2xl:table-cell">{{ formatarNumero(linha.feminino) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </ResponsiveTable>
    </div>

    </div>

    <template v-if="estaExpandido && paginacao && paginacao.last_page > 1" #rodape>
      <Pagination class="!mt-0" :pagination="paginacao" @page-change="$emit('pagina', $event)" />
    </template>
  </TdapPainel>
</template>

<script setup>
/**
 * Tabela detalhada (props.tabela, paginacao do Laravel). Ate `md` vira bloco por
 * linha (ResponsiveTable); a partir dele e tabela. A pagina so repassa o numero
 * da pagina pedida: quem recarrega e a Index.
 */
import { computed, ref } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import { useCollapsibleSection } from '@/Composables/core/useCollapsibleSection';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import FunnelIcon from '@/Components/Icons/FunnelIcon.vue';
import SeletorCompacto from '@/Components/Organisms/Treinamento/Indicadores/SeletorCompacto.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import { formatarNumero, formatarPeriodo } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  tabela: { type: Object, default: () => ({ data: [], meta: null }) },
  carregando: { type: Boolean, default: false },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
});

defineEmits(['pagina', 'alterar', 'limpar']);

// Abre sozinho quando ja ha filtro de REDEC/municipio na URL: filtro escondido
// e recorte invisivel.
// Retratil: comeca aberto e o navegador lembra se foi recolhido.
const { estaExpandido, alternar } = useCollapsibleSection('treinamento-indicadores', 'detalhamento', {
  expandidoPorPadrao: true,
  recolherNoMobile: false,
});

const FILTROS_DA_TABELA = ['redec_id', 'municipio_id'];
const ativos = computed(() => FILTROS_DA_TABELA.filter((k) => props.filtros[k]).length);
const filtrosAbertos = ref(ativos.value > 0);

const valor = (chave) => (props.filtros[chave] ? String(props.filtros[chave]) : '');
const opcoesRedec = computed(() => [{ value: '', label: 'Todas as REDECs' }, ...(props.opcoes.redecs ?? []).map((r) => ({ value: String(r.id), label: r.nome }))]);
const opcoesMunicipio = computed(() => [{ value: '', label: 'Todos os municípios' }, ...(props.opcoes.municipios ?? []).map((m) => ({ value: String(m.id), label: m.nome }))]);

// Turma sem municipio e EAD ou sem local de realizacao: o rotulo diz isso em vez de deixar a celula vazia.
const linhas = computed(() => (props.tabela?.data ?? []).map((linha) => ({
  ...linha,
  local: linha.municipio ?? 'EAD / sem município',
  redec: linha.redec ?? '—',
  mesorregiao: linha.mesorregiao ?? '—',
  periodo: formatarPeriodo(linha.periodo_inicio, linha.periodo_fim),
})));

const camposMobile = [
  { key: 'redec', label: 'REDEC' },
  { key: 'mesorregiao', label: 'Mesorregião' },
  { key: 'periodo', label: 'Período', fullWidth: true },
  { key: 'participantes', label: 'Participantes' },
  { key: 'concluidos', label: 'Concluídos' },
  { key: 'em_andamento', label: 'Em andamento' },
  { key: 'desistentes', label: 'Desistentes' },
  { key: 'masculino', label: 'Masculino' },
  { key: 'feminino', label: 'Feminino' },
];

// Laravel entrega a paginacao em `meta` (Resource) ou na raiz (paginator puro).
const paginacao = computed(() => {
  const m = props.tabela?.meta ?? props.tabela;
  if (!m || m.current_page === undefined) return null;

  return {
    current_page: m.current_page,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 20,
    total: m.total ?? 0,
  };
});
</script>
