<template>
  <Modal :show="show" max-width="5xl" @close="$emit('close')">
    <div ref="painel" role="dialog" aria-modal="true" aria-labelledby="ranking-regras-titulo" aria-describedby="ranking-regras-descricao" class="flex min-h-0 flex-col bg-slate-50 dark:bg-slate-900" @keydown.tab="conterFoco">
      <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-800 sm:px-6">
        <div class="flex items-center gap-3">
          <span class="rounded-xl bg-blue-50 p-2.5 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"><BookOpenIcon class="h-6 w-6" aria-hidden="true" /></span>
          <div>
            <h2 id="ranking-regras-titulo" class="text-lg font-bold text-slate-900 dark:text-white">Regras do placar</h2>
            <p id="ranking-regras-descricao" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Conheça as entregas, os pontos e as condições de cada regra.</p>
          </div>
        </div>
        <button type="button" class="shrink-0 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500 dark:text-slate-300 dark:hover:bg-slate-700" aria-label="Fechar regras do placar" @click="$emit('close')"><XMarkIcon class="h-5 w-5" /></button>
      </header>

      <div class="shrink-0 space-y-4 border-b border-slate-200 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-800 sm:px-6">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4" aria-label="Filtrar regras por situação">
          <button v-for="opcao in situacoes" :key="opcao.value" type="button" :aria-pressed="situacao === opcao.value" class="rounded-xl border px-3 py-2 text-left transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500" :class="situacao === opcao.value ? 'border-blue-500 bg-blue-50 text-blue-800 dark:border-blue-400 dark:bg-blue-500/15 dark:text-blue-200' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-blue-300 dark:border-slate-600 dark:bg-slate-900/40 dark:text-slate-300'" @click="situacao = opcao.value">
            <span class="block text-xl font-bold tabular-nums">{{ opcao.total }}</span><span class="block text-xs">{{ opcao.label }}</span>
          </button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <FilterField v-model="busca" label="Buscar regra" type="text" placeholder="Busque uma entrega ou módulo" />
          <FilterField v-model="modulo" label="Módulo das regras" type="select" :options="modulos" />
        </div>
      </div>

      <div ref="conteudo" class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4 sm:px-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
          <p role="status" aria-live="polite" class="text-xs text-slate-500 dark:text-slate-400">{{ filtradas.length }} de {{ regras.length }} regras</p>
          <button v-if="temFiltros" type="button" class="rounded text-xs font-semibold text-blue-700 underline underline-offset-4 focus-visible:outline focus-visible:outline-2 dark:text-blue-300" @click="limpar">Limpar filtros</button>
        </div>
        <div v-if="filtradas.length" class="grid items-start gap-3 sm:grid-cols-2">
          <RankingRegraCard v-for="regra in filtradas" :key="`${regra.rule_key}-${regra.versao}`" :regra="regra" />
        </div>
        <div v-else class="py-10 text-center">
          <MagnifyingGlassIcon class="mx-auto h-9 w-9 text-slate-400" aria-hidden="true" />
          <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">{{ regras.length ? 'Nenhuma regra encontrada' : 'Nenhuma regra disponível' }}</h3>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ regras.length ? 'Tente outro termo ou remova os filtros.' : 'O catálogo será exibido aqui quando estiver disponível.' }}</p>
          <Button v-if="temFiltros" class="mt-4" variant="outline" size="sm" @click="limpar">Limpar filtros</Button>
        </div>
      </div>

      <footer class="flex shrink-0 items-center justify-between gap-4 border-t border-slate-200 bg-white px-5 py-3 dark:border-slate-700 dark:bg-slate-800 sm:px-6">
        <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">Regras desabilitadas não pontuam automaticamente.</p>
        <Button variant="secondary" size="sm" @click="$emit('close')">Fechar</Button>
      </footer>
    </div>
  </Modal>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { BookOpenIcon, MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import RankingRegraCard from '@/Components/Molecules/Ranking/RankingRegraCard.vue';
import { nomeModuloRanking, tituloRegraRanking, regraDemonstracao, normalizarBusca } from '@/Support/rankingRegras';

const props = defineProps({ show: { type: Boolean, default: false }, regras: { type: Array, default: () => [] } });
defineEmits(['close']);
const painel = ref(null);
const conteudo = ref(null);
const busca = ref('');
const modulo = ref('all');
const situacao = ref('todas');
let focoAnterior = null;
const categorias = {
  todas: () => true,
  habilitadas: (regra) => regra.habilitada && !regraDemonstracao(regra),
  desabilitadas: (regra) => !regra.habilitada && !regraDemonstracao(regra),
  demonstracao: regraDemonstracao,
};
const situacoes = computed(() => [
  { value: 'todas', label: 'Todas as regras' },
  { value: 'habilitadas', label: 'Habilitadas' },
  { value: 'desabilitadas', label: 'Desabilitadas' },
  { value: 'demonstracao', label: 'Demonstração' },
].map((opcao) => ({ ...opcao, total: props.regras.filter(categorias[opcao.value]).length })));
const modulos = computed(() => [{ value: 'all', label: 'Todos os módulos' }, ...Array.from(new Set(props.regras.map((regra) => regra.modulo)))
  .map((modulo) => ({ value: modulo, label: nomeModuloRanking(modulo) }))
  .sort((a, b) => a.label.localeCompare(b.label, 'pt-BR'))]);
const filtradas = computed(() => {
  const termo = normalizarBusca(busca.value.trim());
  return props.regras.filter((regra) => categorias[situacao.value](regra)
    && (modulo.value === 'all' || regra.modulo === modulo.value)
    && normalizarBusca([tituloRegraRanking(regra), nomeModuloRanking(regra.modulo), regra.familia, regra.rule_key].join(' ')).includes(termo));
});
const temFiltros = computed(() => busca.value !== '' || modulo.value !== 'all' || situacao.value !== 'todas');
function limpar() { busca.value = ''; modulo.value = 'all'; situacao.value = 'todas'; }

watch([busca, modulo, situacao], () => { if (conteudo.value) conteudo.value.scrollTop = 0; });
watch(() => props.show, async (aberto) => {
  if (aberto) {
    focoAnterior = document.activeElement;
    await nextTick();
    painel.value?.querySelector('input')?.focus({ preventScroll: true });
  } else {
    await nextTick();
    if (focoAnterior?.isConnected) focoAnterior.focus({ preventScroll: true });
  }
});

function conterFoco(event) {
  const elementos = Array.from(painel.value?.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), summary, a[href], [tabindex="0"]') ?? [])
    .filter((elemento) => elemento.getClientRects().length > 0);
  const primeiro = elementos[0];
  const ultimo = elementos.at(-1);
  if (event.shiftKey && document.activeElement === primeiro) { event.preventDefault(); ultimo?.focus(); }
  else if (!event.shiftKey && document.activeElement === ultimo) { event.preventDefault(); primeiro?.focus(); }
}
</script>
