<template>
  <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Itens em estoque</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Por categoria, um lote por linha, vencimento mais próximo primeiro</p>
      </div>
      <span class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
        {{ itens.length }}{{ temMais ? '+' : '' }} lotes
      </span>
    </header>

    <div v-for="grupo in grupos" :key="grupo.categoria">
      <h4 class="sticky top-0 z-[1] border-b border-slate-200 bg-slate-100/95 px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 backdrop-blur dark:border-slate-700 dark:bg-slate-800/95 dark:text-slate-300">
        {{ grupo.label }} <span class="font-normal normal-case">({{ grupo.itens.length }})</span>
      </h4>
      <ul class="divide-y divide-slate-100 dark:divide-slate-800">
        <li v-for="item in grupo.itens" :key="item.id" class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">
          <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ item.nome }}</p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
              {{ item.sku }}<template v-if="item.patrimonio"> · patrimônio {{ item.patrimonio }}</template>
              · lote {{ item.lote }} · {{ item.almoxarifado }}<template v-if="item.endereco"> · {{ item.endereco }}</template>
            </p>
            <p v-if="item.fornecedor" class="text-xs text-slate-400">Fornecedor/doador: {{ item.fornecedor }}</p>
          </div>
          <div class="flex flex-wrap items-center gap-3 sm:justify-end">
            <div class="text-right">
              <div class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ item.saldo }} <span class="text-xs font-medium text-slate-500">{{ item.unidade_base }}</span></div>
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ item.validade ? `Validade ${formatarData(item.validade)}` : 'Sem validade' }}</div>
            </div>
            <EstoqueStatusBadge :status="item.status" />
            <div class="flex gap-1">
              <button v-if="canMove" type="button" class="rounded-md border border-emerald-300 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500/40 dark:text-emerald-300 dark:hover:bg-emerald-500/10" @click="$emit('entrada', item)">Entrada</button>
              <button v-if="canMove && item.status !== 'critico' && !item.bloqueado" type="button" class="rounded-md border border-blue-300 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-500/40 dark:text-blue-300 dark:hover:bg-blue-500/10" @click="$emit('saida', item)">Saída</button>
              <button v-if="canQuarentena" type="button" class="rounded-md border border-violet-300 px-2.5 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-50 dark:border-violet-500/40 dark:text-violet-300 dark:hover:bg-violet-500/10" @click="$emit('quarentena', item)">
                {{ item.bloqueado ? 'Liberar' : 'Reter' }}
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Skeleton enquanto a proxima pagina chega -->
    <ul v-if="carregando" class="divide-y divide-slate-100 dark:divide-slate-800" aria-hidden="true">
      <li v-for="n in 4" :key="n" class="flex animate-pulse items-center gap-4 px-4 py-4">
        <div class="flex-1 space-y-2">
          <div class="h-3.5 w-1/3 rounded bg-slate-200 dark:bg-slate-700"></div>
          <div class="h-3 w-2/3 rounded bg-slate-100 dark:bg-slate-800"></div>
        </div>
        <div class="h-6 w-16 rounded bg-slate-200 dark:bg-slate-700"></div>
      </li>
    </ul>

    <p v-if="!carregando && itens.length === 0" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
      Nenhum item com saldo para os filtros escolhidos.
    </p>
    <p v-if="erro" class="px-4 py-3 text-center text-sm text-red-600">
      {{ erro }} <button type="button" class="underline" @click="carregarMais">Tentar de novo</button>
    </p>

    <!-- Sentinela do scroll infinito -->
    <div ref="sentinela" class="h-px"></div>
  </section>
</template>

<script setup>
import EstoqueStatusBadge from '@/Components/Molecules/Estoque/EstoqueStatusBadge.vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * Lista de lotes com scroll infinito (#32): a primeira pagina vem nos props
 * da tela; as seguintes, de estoque.lotes.lista quando a sentinela entra na
 * tela. Agrupada por categoria (#33) na ordem que o servidor ja devolve.
 */
const props = defineProps({
  pagina: { type: Object, default: () => ({ data: [], next_page_url: null }) },
  filtros: { type: Object, default: () => ({}) },
  canMove: { type: Boolean, default: false },
  canQuarentena: { type: Boolean, default: false },
});

defineEmits(['entrada', 'saida', 'quarentena']);

const itens = ref([...(props.pagina.data ?? [])]);
const proxima = ref(props.pagina.next_page_url ? 2 : null);
const carregando = ref(false);
const erro = ref('');
const sentinela = ref(null);
const temMais = computed(() => proxima.value !== null);

// Filtro novo ou gravacao: o Inertia troca a primeira pagina e a lista recomeca.
watch(() => props.pagina, (p) => {
  itens.value = [...(p.data ?? [])];
  proxima.value = p.next_page_url ? 2 : null;
  erro.value = '';
});

const grupos = computed(() => {
  const mapa = new Map();
  itens.value.forEach((item) => {
    if (!mapa.has(item.categoria)) mapa.set(item.categoria, { categoria: item.categoria, label: item.categoria_label, itens: [] });
    mapa.get(item.categoria).itens.push(item);
  });
  return [...mapa.values()];
});

async function carregarMais() {
  if (carregando.value || proxima.value === null) return;
  carregando.value = true;
  erro.value = '';
  try {
    const axios = window.axios || (await import('axios')).default;
    const { data } = await axios.get(route('estoque.lotes.lista', { ...props.filtros, page: proxima.value }));
    itens.value.push(...data.data);
    proxima.value = data.next_page_url ? proxima.value + 1 : null;
  } catch (e) {
    erro.value = e?.response?.data?.message || 'Não foi possível carregar mais itens.';
  } finally {
    carregando.value = false;
  }
}

let observador = null;
onMounted(() => {
  observador = new IntersectionObserver((entradas) => {
    if (entradas.some((e) => e.isIntersecting)) carregarMais();
  }, { rootMargin: '300px' });
  if (sentinela.value) observador.observe(sentinela.value);
});
onBeforeUnmount(() => observador?.disconnect());

function formatarData(iso) {
  const [a, m, d] = String(iso).slice(0, 10).split('-');
  return `${d}/${m}/${a}`;
}
</script>
