<template>
  <div class="orgaos-index">
    <!-- Header -->
    <PageHeader
      title="Orgaos de Defesa Civil"
      description="Gestao de COMPDEC, REDEC e CEDEC"
      :icon="BuildingOfficeIcon"
      variant="gradient"
    >
      <template #actions>
        <Button
          v-if="canManage"
          variant="primary"
          size="md"
          :icon="PlusIcon"
          icon-position="left"
          @click="handleCreate"
        >
          Novo Orgao
        </Button>
      </template>
    </PageHeader>

    <!-- Stats Cards (Organism reusavel) -->
    <OrgaoStatsCards :statistics="statistics" />

    <!-- Filtros -->
    <OrgaosFiltersSection
      :filters="localFilters"
      :municipalities="filterOptions?.municipalities || []"
      @filter-change="handleFilterChange"
      @filter-reset="handleFilterReset"
    />

    <!-- Tabela -->
    <ListSurface
      title="Órgãos"
      subtitle="Cadastro e acompanhamento de COMPDEC, REDEC e CEDEC"
      :count="orgaos.total ?? orgaos.data.length"
      :icon="BuildingOfficeIcon"
      class="relative mb-8"
    >
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>

      <div class="overflow-x-auto -mx-px">
      <table class="w-full text-sm">
        <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/50">
          <tr>
            <th class="px-4 py-3 text-left whitespace-nowrap">Código</th>
            <th class="px-4 py-3 text-left whitespace-nowrap">Nome</th>
            <th class="px-4 py-3 text-left whitespace-nowrap">Tipo</th>
            <th class="px-4 py-3 text-left whitespace-nowrap">Município</th>
            <th class="px-4 py-3 text-left whitespace-nowrap">Status</th>
            <th class="px-4 py-3 text-left whitespace-nowrap">Usuários</th>
            <th class="px-4 py-3 text-right whitespace-nowrap w-48 min-w-48">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/30">
          <tr v-if="orgaos.data.length === 0">
            <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              Nenhum órgão encontrado
            </td>
          </tr>
          <tr v-for="orgao in orgaos.data" :key="orgao.id" class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/20">
            <td class="px-4 py-4 whitespace-nowrap">
              <Text variant="mono">{{ orgao.codigo }}</Text>
            </td>
            <td class="px-4 py-4">
              <Text variant="bold">{{ orgao.nome }}</Text>
            </td>
            <td class="px-4 py-4 whitespace-nowrap">
              <TipoOrgaoBadge :tipo="orgao.tipo" />
            </td>
            <td class="px-4 py-4 whitespace-nowrap">
              <Text variant="muted">{{ orgao.municipio?.nome || '-' }}</Text>
            </td>
            <td class="px-4 py-4 whitespace-nowrap">
              <StatusOrgaoBadge :status="orgao.status" />
            </td>
            <td class="px-4 py-4 whitespace-nowrap">
              <Text variant="muted">{{ orgao.usuarios_count || 0 }}</Text>
            </td>
            <td class="px-4 py-4 text-right w-48 min-w-48">
              <div class="flex items-center justify-end">
                <TableActions
                  :show-view="true"
                  :show-edit="canManage"
                  :show-attachments="false"
                  :show-delete="canDeleteOrgao(orgao)"
                  @view="handleView(orgao.id)"
                  @edit="handleEdit(orgao.id)"
                  @delete="handleDelete(orgao)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </ListSurface>

    <!-- Paginacao -->
    <Pagination
      v-if="orgaos.data.length > 0"
      :current-page="orgaos.current_page"
      :last-page="orgaos.last_page"
      :total="orgaos.total"
      @page-change="handlePageChange"
    />

    <ConfirmDialog
      :is-open="deleteDialog.open"
      variant="danger"
      title="Excluir Orgao"
      :message="deleteDialog.message"
      description="Esta acao remove o orgao do cadastro. Orgaos com usuarios vinculados ou subordinados nao podem ser excluidos."
      confirm-text="Excluir"
      cancel-text="Cancelar"
      :loading="deleteDialog.loading"
      @confirm="confirmDelete"
      @cancel="closeDeleteDialog"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import StatusOrgaoBadge from '@/Components/Molecules/Compdec/StatusOrgaoBadge.vue';
import TipoOrgaoBadge from '@/Components/Molecules/Compdec/TipoOrgaoBadge.vue';
import OrgaoStatsCards from '@/Components/Organisms/Compdec/OrgaoStatsCards.vue';
import OrgaosFiltersSection from '@/Components/Organisms/Compdec/OrgaosFiltersSection.vue';
import ListSurface from '@/Components/Organisms/Table/ListSurface.vue';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import { useToast } from '@/composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  orgaos: {
    type: Object,
    required: true,
  },
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      ativos: 0,
      por_tipo: { compdec: 0, redec: 0, cedec: 0 },
    }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
  canManage: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

const loading = ref(false);
const localFilters = ref({ ...props.filters });
const { toast } = useToast();
const deleteDialog = ref({
  open: false,
  loading: false,
  orgao: null,
  message: '',
});

function applyFilters() {
  loading.value = true;
  router.get(route('compdec.index'), localFilters.value, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    },
  });
}

function handleFilterChange(newFilters) {
  localFilters.value = { ...newFilters };
  applyFilters();
}

function handleFilterReset() {
  localFilters.value = { tipo: '', status: '', search: '', municipio: '' };
  applyFilters();
}

function handlePageChange(page) {
  loading.value = true;
  router.get(route('compdec.index'), {
    ...localFilters.value,
    page,
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    },
  });
}

function handleCreate() {
  router.visit(route('compdec.create'));
}

function handleView(id) {
  router.visit(route('compdec.show', id));
}

function handleEdit(id) {
  router.visit(route('compdec.edit', id));
}

function canDeleteOrgao(orgao) {
  return props.canDelete
    && Number(orgao?.usuarios_count || 0) === 0
    && Number(orgao?.subordinados_count || 0) === 0;
}

function handleDelete(orgao) {
  deleteDialog.value = {
    open: true,
    loading: false,
    orgao,
    message: `Tem certeza que deseja excluir ${orgao.nome}?`,
  };
}

function closeDeleteDialog() {
  if (deleteDialog.value.loading) return;
  deleteDialog.value.open = false;
  deleteDialog.value.orgao = null;
}

function confirmDelete() {
  const orgao = deleteDialog.value.orgao;
  if (!orgao) return;

  let handled = false;
  deleteDialog.value.loading = true;
  router.delete(route('compdec.destroy', orgao.id), {
    preserveScroll: true,
    onSuccess: () => {
      handled = true;
      toast('Orgao excluido com sucesso.', 'success');
      deleteDialog.value.loading = false;
      closeDeleteDialog();
    },
    onError: () => {
      handled = true;
      toast('Nao foi possivel excluir este orgao. Verifique vinculos, subordinados e permissoes.', 'error');
    },
    onFinish: () => {
      deleteDialog.value.loading = false;
      if (!handled) {
        toast('Nao foi possivel excluir este orgao. A requisicao foi rejeitada pelo servidor.', 'error');
      }
    },
  });
}
</script>

<style scoped>
.orgaos-index {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
}

.loading-overlay {
  @apply bg-white/80 dark:bg-slate-900/80;
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

</style>
