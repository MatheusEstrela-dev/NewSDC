<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PassagemHandshakeBanner from '@/Components/Molecules/Plantao/PassagemHandshakeBanner.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import AceitarPassagemModal from '@/Components/Organisms/Plantao/AceitarPassagemModal.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import AbrirPlantaoModal from '@/Components/Organisms/Plantao/AbrirPlantaoModal.vue';
import EncerrarTurnoModal from '@/Components/Organisms/Plantao/EncerrarTurnoModal.vue';
import PlantaoFiltersSection from '@/Components/Organisms/Plantao/PlantaoFiltersSection.vue';
import PlantaoGrid from '@/Components/Organisms/Plantao/PlantaoGrid.vue';
import PrintPassagemModal from '@/Components/Organisms/Plantao/Print/PrintPassagemModal.vue';
import PlantaoStatsCards from '@/Components/Organisms/Plantao/PlantaoStatsCards.vue';
import PlantaoTable from '@/Components/Organisms/Plantao/PlantaoTable.vue';
import RelatorioPassagemPanel from '@/Components/Organisms/Plantao/RelatorioPassagemPanel.vue';
import { useExport } from '@/Composables/useExport';
import { useMobile } from '@/Composables/useMobile';
import { ArrowDownTrayIcon, NewspaperIcon } from '@heroicons/vue/24/outline';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  plantoes: {
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
    default: () => ({}),
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
  canExport: {
    type: Boolean,
    default: false,
  },
  // Um item por turno ATIVO. Cada um ganha o proprio botao de encerrar: com
  // apenas o mais recente, um turno anterior ficava sem saida (spec 4.3).
  turnosAtivos: {
    type: Array,
    default: () => [],
  },
  // Um item por turno PENDENTE_ACEITE, cada um com o proprio banner de aceite.
  turnosPendentes: {
    type: Array,
    default: () => [],
  },
  canEncerrar: {
    type: Boolean,
    default: false,
  },
  canAceitar: {
    type: Boolean,
    default: false,
  },
  // Reservado para o botao de relatorio de passagem (Fase 4 / Task 12).
  canRelatorio: {
    type: Boolean,
    default: false,
  },
  canEscala: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['view', 'edit', 'filter', 'abrir-plantao']);

const viewMode = ref('table');
const showAbrirModal = ref(false);
const showEncerrarModal = ref(false);
const showAceitarModal = ref(false);
// Qual turno da lista esta sendo encerrado/conferido. Os modais leem `turno`,
// entao a selecao e explicita em vez de implicita no "mais recente".
const turnoParaEncerrar = ref(null);
const turnoParaAceitar = ref(null);
const { isMobile } = useMobile();

const page = usePage();

// Erro de dominio da abertura de turno (PassagemInvalidaException traduzida
// pelo PassagemAbrirController). Chave propria: o redirect de volta recria o
// componente e fecha o modal, entao a mensagem precisa de casa na pagina.
const erroAbertura = computed(() => page.props.errors?.abertura || '');

// O painel do relatorio segue o turno mais relevante: a pendencia mais recente
// e, sem nenhuma, o turno ativo mais recente. Um painel por turno exigiria
// section-id proprio no CollapsibleSection (compartilhado, fora de escopo).
const plantaoDoRelatorio = computed(
  () => props.turnosPendentes[0]?.id ?? props.turnosAtivos[0]?.id ?? null,
);

// So mostra o botao de encerrar para turnos que o usuario logado pode
// encerrar: o proprio (dono do turno) ou, com `encerrar_alheio`, qualquer um.
// A regra ja vem decidida do backend em `turno.pode_encerrar` (PlantaoIndexController) -
// o frontend so filtra, nao recalcula quem e dono de que.
const turnosEncerraveis = computed(
  () => (props.canEncerrar ? props.turnosAtivos.filter((turno) => turno.pode_encerrar) : []),
);

const rotuloEncerrar = (turno) => (
  turnosEncerraveis.value.length > 1
    ? `Encerrar ${turno.data} (${turno.periodo})`
    : 'Encerrar turno'
);

const abrirEncerrarModal = (turno) => {
  turnoParaEncerrar.value = turno;
  showEncerrarModal.value = true;
};

const abrirAceitarModal = (turno) => {
  turnoParaAceitar.value = turno;
  showAceitarModal.value = true;
};

const handleFrota = () => {
  router.visit(route('plantao.viaturas.index'));
};

const handleEscala = () => {
  router.visit(route('plantao.escala.index'));
};

// Card de estatistica como filtro rapido: recebe o status ('' = Total, limpa o status)
// e preserva os demais filtros ativos (periodo, search).
const handleStatFilter = (status) => {
  emit('filter', { ...props.filters, status: status || undefined });
};

// Export Setup
const {
  showExportModal,
  handleExport: triggerExport
} = useExport('plantao.export');

const handleExportCsv = (params) => {
  triggerExport(params, props.filters);
};

const handleBuscarNoticias = () => {
  router.visit(route('plantao.noticias'));
};

// =========================
// Modal de Impressao (passagem de servico do turno selecionado)
// =========================
const printModalOpen = ref(false);
const selectedPlantao = ref(null);

function handlePrint(id) {
  selectedPlantao.value = props.plantoes.find((p) => p.id === id) || null;
  printModalOpen.value = true;
}

function closePrintModal() {
  printModalOpen.value = false;
  selectedPlantao.value = null;
}
</script>

<template>
  <div class="plantao-container">
    <!-- Header Padronizado -->
    <PageHeader
      title="Plantão Diário"
      description="Gestão de turnos e lançamentos operacionais"
      :icon="ClockIcon"
      :icon-image="moduleIcon('plantao')"
      variant="gradient"
      icon-class="text-blue-600 dark:text-blue-400"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <!-- Toggle Grade/Tabela - Componente Reutilizavel -->
          <ViewModeToggle v-model="viewMode" />

          <Button
            variant="secondary"
            size="md"
            :icon="NewspaperIcon"
            icon-position="left"
            @click="handleBuscarNoticias"
          >
            <span class="hidden sm:inline">Buscar Noticias</span>
            <span class="sm:hidden">Noticias</span>
          </Button>

          <Button
            variant="secondary"
            size="md"
            :icon="TruckIcon"
            icon-position="left"
            @click="handleFrota"
          >
            Frota
          </Button>

          <Button
            v-if="canEscala"
            variant="secondary"
            size="md"
            :icon="CalendarIcon"
            icon-position="left"
            @click="handleEscala"
          >
            Escala
          </Button>

          <Button
            v-for="turno in turnosEncerraveis"
            :key="turno.id"
            variant="danger"
            size="md"
            :icon="ClipboardDocumentListIcon"
            icon-position="left"
            @click="abrirEncerrarModal(turno)"
          >
            {{ rotuloEncerrar(turno) }}
          </Button>

          <Button
            v-if="canExport"
            variant="success"
            size="md"
            :icon="ArrowDownTrayIcon"
            icon-position="left"
            @click="showExportModal = true"
          >
            <span class="hidden sm:inline">Exportar Excel</span>
            <span class="sm:hidden">Exp</span>
          </Button>

          <Button
            v-if="canCreate"
            variant="primary"
            size="md"
            :icon="PlusIcon"
            icon-position="left"
            @click="showAbrirModal = true"
          >
            <span class="hidden sm:inline">Abrir Plantão</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Erro de abertura de turno -->
    <div
      v-if="erroAbertura"
      class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300"
    >
      {{ erroAbertura }}
    </div>

    <!-- Um banner por passagem pendente de aceite: nenhuma fica sem caminho -->
    <PassagemHandshakeBanner
      v-for="turno in turnosPendentes"
      :key="turno.id"
      :turno="turno"
      :pode-aceitar="canAceitar"
      class="mb-6"
      @conferir="abrirAceitarModal(turno)"
    />

    <!-- Smart Cards -->
    <PlantaoStatsCards
      :statistics="statistics"
      class="mb-6"
      @filter="handleStatFilter"
    />

    <!-- Relatorio de passagem de servico: texto pronto para colar no WhatsApp -->
    <RelatorioPassagemPanel
      v-if="canRelatorio && plantaoDoRelatorio"
      :key="plantaoDoRelatorio"
      :plantao-id="plantaoDoRelatorio"
      class="mb-6"
    />

    <!-- Filtros -->
    <PlantaoFiltersSection
      :filters="filters"
      :filter-options="filterOptions"
      @filter-change="emit('filter', $event)"
      @filter-reset="emit('filter', {})"
    />

    <!-- Grid (Mobile ou Desktop selecionado) -->
    <PlantaoGrid
      v-if="viewMode === 'grid' || isMobile"
      :plantoes="plantoes"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="emit('view', $event)"
      @print="handlePrint"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
    />

    <!-- Tabela (Desktop selecionado) -->
    <PlantaoTable
      v-else-if="viewMode === 'table' && !isMobile"
      :plantoes="plantoes"
      :can-edit="canEdit"
      :can-delete="canDelete"
      @view="emit('view', $event)"
      @print="handlePrint"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
    />

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="(page) => emit('filter', { page })"
      />
    </div>

    <!-- Modal: Exportação CSV -->
    <ExportCsvModal
      :show="showExportModal"
      module-name="Plantão Diário"
      @close="showExportModal = false"
      @export="handleExportCsv"
    />

    <!-- Modal: Abrir Plantão -->
    <AbrirPlantaoModal
      :show="showAbrirModal"
      :periodos="filterOptions?.periodos"
      @close="showAbrirModal = false"
      @submit="(data) => { showAbrirModal = false; emit('abrir-plantao', data); }"
    />

    <!-- Modal: Encerrar turno (quem sai declara o estado das viaturas) -->
    <EncerrarTurnoModal
      :show="showEncerrarModal"
      :turno="turnoParaEncerrar"
      :filter-options="filterOptions"
      @close="showEncerrarModal = false"
      @saved="showEncerrarModal = false"
    />

    <!-- Modal: Aceitar passagem (quem assume confere e aceita ou aponta divergencia) -->
    <AceitarPassagemModal
      :show="showAceitarModal"
      :turno="turnoParaAceitar"
      @close="showAceitarModal = false"
      @saved="showAceitarModal = false"
    />

    <!-- Modal: Imprimir passagem de servico do turno selecionado -->
    <PrintPassagemModal
      :show="printModalOpen"
      :plantao="selectedPlantao"
      @close="closePrintModal"
    />
  </div>
</template>

<style scoped>
.plantao-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
