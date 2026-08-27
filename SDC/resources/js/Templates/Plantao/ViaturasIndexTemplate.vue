<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import FunnelIcon from '@/Components/Icons/FunnelIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import MovimentacaoModal from '@/Components/Organisms/Plantao/MovimentacaoModal.vue';
import ViaturaFormModal from '@/Components/Organisms/Plantao/ViaturaFormModal.vue';
import ViaturasGrid from '@/Components/Organisms/Plantao/ViaturasGrid.vue';
import ViaturasTable from '@/Components/Organisms/Plantao/ViaturasTable.vue';
import { useMobile } from '@/Composables/useMobile';
import { moduleIcon } from '@/Support/moduleIcons';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
  viaturas: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    required: true,
  },
  pagination: {
    type: Object,
    default: null,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({ status: [], localizacoes: [], niveis: [] }),
  },
  // Ja mapeado para {value, label} pelo ViaturaIndexController -- consumido pelo
  // FormSelect de condutor do MovimentacaoModal.
  condutores: {
    type: Array,
    default: () => [],
  },
  // Turno ATIVO corrente. Vai no payload da saida para amarrar a movimentacao
  // ao turno de quem esta de servico; null quando nao ha turno aberto.
  plantaoAtivoId: {
    type: Number,
    default: null,
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
  canMovimentar: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['filter', 'edit', 'delete']);

const viewMode = ref('table');
const { isMobile } = useMobile();

const localFiltros = reactive({
  search: props.filters?.search ?? '',
  localizacao: props.filters?.localizacao ?? '',
  ativo: props.filters?.ativo ?? '',
});

watch(
  () => props.filters,
  (novos) => {
    Object.assign(localFiltros, {
      search: novos?.search ?? '',
      localizacao: novos?.localizacao ?? '',
      ativo: novos?.ativo ?? '',
    });
  },
);

const showFormModal = ref(false);
const viaturaEmEdicao = ref(null);

const openCreateModal = () => {
  viaturaEmEdicao.value = null;
  showFormModal.value = true;
};

const openEditModal = (id) => {
  viaturaEmEdicao.value = props.viaturas.find((v) => v.id === id) ?? null;
  showFormModal.value = true;
};

const closeFormModal = () => {
  showFormModal.value = false;
  viaturaEmEdicao.value = null;
};

const onSaved = () => {
  closeFormModal();
  emit('filter', { ...props.filters });
};

const showMovimentacaoModal = ref(false);
const viaturaEmMovimentacao = ref(null);
const modoMovimentacao = ref('saida');

const openMovimentacaoModal = (id) => {
  const viatura = props.viaturas.find((v) => v.id === id) ?? null;
  if (!viatura) return;

  viaturaEmMovimentacao.value = viatura;
  modoMovimentacao.value = viatura.movimentacao_aberta_id ? 'retorno' : 'saida';
  showMovimentacaoModal.value = true;
};

const closeMovimentacaoModal = () => {
  showMovimentacaoModal.value = false;
  viaturaEmMovimentacao.value = null;
};

const onMovimentacaoSaved = () => {
  closeMovimentacaoModal();
  emit('filter', { ...props.filters });
};

// Card de estatistica como filtro rapido: '' (Total) limpa o status.
const handleStatFilter = (status) => {
  emit('filter', { ...props.filters, status: status || undefined });
};

const aplicarFiltros = () => {
  emit('filter', {
    ...props.filters,
    search: localFiltros.search || undefined,
    localizacao: localFiltros.localizacao || undefined,
    ativo: localFiltros.ativo === '' ? undefined : localFiltros.ativo,
  });
};

const limparFiltros = () => {
  Object.assign(localFiltros, { search: '', localizacao: '', ativo: '' });
  emit('filter', { status: props.filters?.status || undefined });
};
</script>

<template>
  <div class="viaturas-container">
    <PageHeader
      title="Frota de Viaturas"
      description="Cadastro e situacao das viaturas do plantao"
      :icon="TruckIcon"
      :icon-image="moduleIcon('plantao')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <ViewModeToggle v-model="viewMode" />

          <Button
            v-if="canCreate"
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="openCreateModal"
          >
            <span class="hidden sm:inline">Nova Viatura</span>
            <span class="sm:hidden">Nova</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <StatCardsGrid :colunas="4">
      <StatCard
        title="Total"
        :value="statistics.total"
        variant="info"
        clickable
        @click="handleStatFilter('')"
      />
      <StatCard
        title="Disponiveis"
        :value="statistics.disponiveis"
        variant="success"
        clickable
        @click="handleStatFilter('DISPONIVEL')"
      />
      <StatCard
        title="Em transito"
        :value="statistics.em_transito"
        variant="info"
        clickable
        @click="handleStatFilter('EM_TRANSITO')"
      />
      <StatCard
        title="Indisponiveis"
        :value="statistics.indisponiveis"
        variant="warning"
        clickable
        @click="handleStatFilter('MANUTENCAO,CEDIDA,INDISPONIVEL')"
      />
    </StatCardsGrid>

    <CollapsibleSection
      namespace="plantao"
      section-id="viaturas-filtros"
      title="Filtros de pesquisa"
      :icon="FunnelIcon"
      tom="neutro"
      class="mb-6"
      :expandido-por-padrao="false"
    >
      <form class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="aplicarFiltros">
        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Busca</span>
          <input
            v-model="localFiltros.search"
            type="text"
            placeholder="Placa, prefixo ou modelo"
            class="w-full rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
        </label>

        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Localizacao</span>
          <select
            v-model="localFiltros.localizacao"
            class="w-full rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">Todas</option>
            <option v-for="opt in filterOptions.localizacoes" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </label>

        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Ativa</span>
          <select
            v-model="localFiltros.ativo"
            class="w-full rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">Indiferente</option>
            <option value="1">Sim</option>
            <option value="0">Nao</option>
          </select>
        </label>

        <div class="flex items-end gap-2">
          <button
            type="button"
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            @click="limparFiltros"
          >
            Limpar
          </button>
          <button
            type="submit"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          >
            Pesquisar
          </button>
        </div>
      </form>
    </CollapsibleSection>

    <ViaturasGrid
      v-if="viewMode === 'grid' || isMobile"
      :viaturas="viaturas"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-movimentar="canMovimentar"
      @edit="openEditModal"
      @delete="(id) => emit('delete', id)"
      @movimentacao="openMovimentacaoModal"
    />

    <ViaturasTable
      v-else
      :viaturas="viaturas"
      :can-edit="canEdit"
      :can-delete="canDelete"
      :can-movimentar="canMovimentar"
      @edit="openEditModal"
      @delete="(id) => emit('delete', id)"
      @movimentacao="openMovimentacaoModal"
    />

    <Pagination
      v-if="pagination"
      :pagination="pagination"
      @page-change="(page) => emit('filter', { ...filters, page })"
    />

    <ViaturaFormModal
      :show="showFormModal"
      :viatura="viaturaEmEdicao"
      :filter-options="filterOptions"
      @close="closeFormModal"
      @saved="onSaved"
    />

    <MovimentacaoModal
      :show="showMovimentacaoModal"
      :modo="modoMovimentacao"
      :viatura="viaturaEmMovimentacao"
      :condutores="condutores"
      :plantao-ativo-id="plantaoAtivoId"
      :filter-options="filterOptions"
      @close="closeMovimentacaoModal"
      @saved="onMovimentacaoSaved"
    />
  </div>
</template>

<style scoped>
.viaturas-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
