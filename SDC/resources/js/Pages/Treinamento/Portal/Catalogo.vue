<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TreinamentoGrid from '@/Components/Organisms/Treinamento/TreinamentoGrid.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PastasDeCategoria from '@/Components/Organisms/Treinamento/PastasDeCategoria.vue';
import CabecalhoDaPasta from '@/Components/Molecules/Treinamento/CabecalhoDaPasta.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  treinamentos: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  // Pastas das categorias de oferta (contagem do catalogo com a busca aplicada).
  categorias: { type: Array, default: () => [] },
});

const categoriaAtiva = computed(() => props.categorias.find((c) => c.ativa) ?? null);
// Raiz com busca: lista plana de todas as categorias.
const emBusca = computed(() => !categoriaAtiva.value && !!props.filters.search);
const mostraCursos = computed(() => categoriaAtiva.value !== null || emBusca.value);

const pagination = computed(() => {
  const m = props.treinamentos?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 12,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

function irParaPagina(page) {
  router.visit(route('portal.treinamento.catalogo'), {
    data: { ...props.filters, page },
    preserveState: true,
  });
}

function handleView(id) {
  const treinamento = (props.treinamentos.data || []).find((t) => t.id === id);
  if (treinamento?.link_publico_slug) {
    router.visit(route('portal.treinamento.eventos.show', treinamento.link_publico_slug));
  }
}

// Busca e categoria se combinam; mudar qualquer uma volta para a pagina 1.
function navegar(alteracao, { replace }) {
  const filtros = { search: props.filters.search, categoria: props.filters.categoria, ...alteracao };
  router.get(
    route('portal.treinamento.catalogo'),
    Object.fromEntries(Object.entries(filtros).filter(([, valor]) => valor)),
    { preserveState: true, preserveScroll: replace, replace },
  );
}

// Digitar na busca refina no lugar (replace); abrir/fechar pasta entra no historico.
function handleSearch(evt) {
  navegar({ search: evt.target.value }, { replace: true });
}

function abrirPasta(valor) {
  navegar({ categoria: valor }, { replace: false });
}

function limparBusca() {
  navegar({ search: null }, { replace: false });
}
</script>

<template>
  <div class="pb-8">
    <div class="mb-6">
      <Heading :level="1" class="text-2xl font-bold text-slate-800 dark:text-slate-100">Cursos e Eventos</Heading>
      <Text size="sm" color="muted">Confira os cursos e eventos abertos para inscrição da Defesa Civil de Minas Gerais.</Text>
    </div>

    <div class="mb-6 max-w-sm">
      <input
        type="text"
        :value="filters.search"
        :placeholder="categoriaAtiva ? 'Buscar nesta categoria...' : 'Buscar por título...'"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
        @change="handleSearch"
      />
    </div>

    <PastasDeCategoria v-if="!mostraCursos" :categorias="categorias" :desabilitar-vazias="true" @abrir="abrirPasta" />

    <template v-else>
      <CabecalhoDaPasta v-if="categoriaAtiva" :categoria="categoriaAtiva" @voltar="abrirPasta(null)" />
      <div
        v-else
        class="mb-5 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm dark:border-slate-700/60 dark:bg-slate-800/60"
      >
        <span class="font-semibold text-slate-800 dark:text-slate-100">Resultados da busca</span>
        <button
          type="button"
          class="min-h-[44px] font-medium text-blue-600 hover:underline focus:outline-none focus-visible:underline dark:text-blue-400 sm:min-h-0"
          @click="limparBusca"
        >
          Limpar busca
        </button>
      </div>

      <Text v-if="!treinamentos.data.length" color="muted">
        {{ categoriaAtiva ? 'Nenhuma capacitação nesta categoria ainda.' : 'Nenhum resultado para a busca.' }}
      </Text>
      <TreinamentoGrid v-else :treinamentos="treinamentos.data" @view="handleView" />
    </template>

    <div v-if="mostraCursos && pagination" class="mt-6">
      <Pagination :pagination="pagination" @page-change="irParaPagina" />
    </div>
  </div>
</template>
