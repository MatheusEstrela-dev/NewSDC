<template>
  <div class="estoque-container">
    <PageHeader
      title="Estoque"
      description="Donativos e insumos por almoxarifado, lote e validade"
      :icon="ArchiveBoxIcon"
      :icon-image="moduleIcon('estoque')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="exportar">
            <span class="hidden sm:inline">Exportar</span>
          </Button>
          <Button v-if="canCreate" variant="secondary" size="md" :icon="PlusIcon" icon-position="left" @click="abrirCadastro('produto')">Produto</Button>
          <Button v-if="canMove" variant="success" size="md" :icon="ArrowDownTrayIcon" icon-position="left" @click="abrirEntrada(null)">Entrada</Button>
          <Button v-if="canMove" variant="primary" size="md" :icon="ArrowUpTrayIcon" icon-position="left" @click="abrirSaida(null)">Saída</Button>
        </div>
      </template>
    </PageHeader>

    <div v-if="erroGeral" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
      {{ erroGeral }}
    </div>

    <EstoqueStatsCards :statistics="statistics" @filtrar="aplicarFiltros({ ...filters, ...$event })" />

    <EstoqueFiltersSection :filters="filters" :filter-options="filterOptions" @apply="aplicarFiltros" @clear="aplicarFiltros({})" />

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_22rem]">
      <div class="min-w-0 space-y-6">
        <EstoqueAlertsPanel v-if="activeSection === 'dashboard' && alertas.length" :alertas="alertas" />
        <EstoqueLotesLista
          v-if="activeSection !== 'kits' && activeSection !== 'movimentacoes'"
          :pagina="lotes"
          :filtros="filters"
          :can-move="canMove"
          :can-quarentena="canQuarentena"
          @entrada="abrirEntrada"
          @saida="abrirSaida"
          @quarentena="alternarQuarentena"
        />
        <EstoqueKitsPanel
          v-if="activeSection === 'kits'"
          :kits="kits"
          :can-move="canMove"
          :can-create="canCreateKit"
          @saida="(kit) => abrirSaida({ kit })"
          @novo="abrirCadastro('kit')"
        />
        <EstoqueMovementsPanel v-if="activeSection === 'movimentacoes'" :movimentacoes="movimentacoesExibidas" />
      </div>

      <aside class="space-y-6">
        <EstoqueOcupacaoPanel
          :almoxarifados="ocupacao"
          :selecionado="String(filters.almoxarifado_id ?? '')"
          :can-create="canCreate"
          @filtrar="(id) => aplicarFiltros({ ...filters, almoxarifado_id: String(filters.almoxarifado_id) === String(id) ? undefined : id })"
          @novo="abrirCadastro('almoxarifado')"
        />
        <EstoqueKitsPanel
          v-if="activeSection !== 'kits'"
          :kits="kits"
          :can-move="canMove"
          :can-create="canCreateKit"
          @saida="(kit) => abrirSaida({ kit })"
          @novo="abrirCadastro('kit')"
        />
        <EstoqueMovementsPanel v-if="activeSection !== 'movimentacoes'" :movimentacoes="movimentacoesExibidas" />
      </aside>
    </div>

    <EstoqueEntradaModal :show="modal === 'entrada'" :catalogo="catalogo" :almoxarifados="filterOptions.almoxarifados ?? []" :inicial="inicial" @close="fecharModal" />
    <EstoqueSaidaModal
      :show="modal === 'saida'"
      :catalogo="catalogo"
      :almoxarifados="filterOptions.almoxarifados ?? []"
      :municipios="municipios"
      :kits="kits"
      :inicial="inicial"
      @close="fecharModal"
    />
    <EstoqueCadastroModal
      :show="modal === 'cadastro'"
      :tipo="tipoCadastro"
      :categorias="filterOptions.categorias ?? []"
      :catalogo="catalogo"
      :municipios="municipios"
      @close="fecharModal"
    />
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import EstoqueAlertsPanel from '@/Components/Organisms/Estoque/EstoqueAlertsPanel.vue';
import EstoqueCadastroModal from '@/Components/Organisms/Estoque/EstoqueCadastroModal.vue';
import EstoqueEntradaModal from '@/Components/Organisms/Estoque/EstoqueEntradaModal.vue';
import EstoqueFiltersSection from '@/Components/Organisms/Estoque/EstoqueFiltersSection.vue';
import EstoqueKitsPanel from '@/Components/Organisms/Estoque/EstoqueKitsPanel.vue';
import EstoqueLotesLista from '@/Components/Organisms/Estoque/EstoqueLotesLista.vue';
import EstoqueMovementsPanel from '@/Components/Organisms/Estoque/EstoqueMovementsPanel.vue';
import EstoqueOcupacaoPanel from '@/Components/Organisms/Estoque/EstoqueOcupacaoPanel.vue';
import EstoqueSaidaModal from '@/Components/Organisms/Estoque/EstoqueSaidaModal.vue';
import EstoqueStatsCards from '@/Components/Organisms/Estoque/EstoqueStatsCards.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { ArchiveBoxIcon, ArrowDownTrayIcon, ArrowUpTrayIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  activeSection: { type: String, default: 'dashboard' },
  lotes: { type: Object, default: () => ({ data: [], next_page_url: null }) },
  statistics: { type: Object, default: () => ({}) },
  ocupacao: { type: Array, default: () => [] },
  alertas: { type: Array, default: () => [] },
  kits: { type: Array, default: () => [] },
  movimentacoes: { type: Array, default: () => [] },
  catalogo: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  filterOptions: { type: Object, default: () => ({}) },
  canCreate: { type: Boolean, default: false },
  canCreateKit: { type: Boolean, default: false },
  canExport: { type: Boolean, default: false },
  canMove: { type: Boolean, default: false },
  canQuarentena: { type: Boolean, default: false },
});

const modal = ref(null);
const inicial = ref(null);
const tipoCadastro = ref('produto');
const page = usePage();
const erroGeral = computed(() => page.props.errors?.estoque ?? null);

// O painel antigo esperava `status`; o extrato traz o lote e quem lancou.
const movimentacoesExibidas = computed(() => props.movimentacoes.map((m) => ({
  ...m,
  tipo: m.tipo === 'saida' ? 'Saída' : 'Entrada',
  status: m.usuario ?? '',
})));

function aplicarFiltros(filtros) {
  const limpos = Object.fromEntries(Object.entries(filtros).filter(([, v]) => v !== '' && v !== null && v !== undefined));
  router.get(route(routeDaSecao()), limpos, { preserveState: true, preserveScroll: true, replace: true });
}

function routeDaSecao() {
  return { produtos: 'estoque.produtos.index', kits: 'estoque.kits.index', movimentacoes: 'estoque.movimentacoes.index' }[props.activeSection] ?? 'estoque.index';
}

function abrirEntrada(lote) {
  inicial.value = lote;
  modal.value = 'entrada';
}

function abrirSaida(origem) {
  inicial.value = origem;
  modal.value = 'saida';
}

function abrirCadastro(tipo) {
  tipoCadastro.value = tipo;
  modal.value = 'cadastro';
}

function fecharModal() {
  modal.value = null;
  inicial.value = null;
}

function alternarQuarentena(lote) {
  router.patch(route('estoque.lotes.quarentena', lote.id), { bloqueado: !lote.bloqueado }, { preserveScroll: true });
}

function exportar() {
  window.location.href = route('estoque.export-excel', props.filters);
}
</script>

<style scoped>
.estoque-container {
  @apply w-full pb-8;
}
</style>
