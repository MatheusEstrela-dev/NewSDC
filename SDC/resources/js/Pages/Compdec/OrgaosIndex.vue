<template>
  <AuthenticatedLayout title="Órgãos - COMPDEC/REDEC/CEDEC">
    <div class="orgaos-index">
      <!-- Header Padronizado -->
      <PageHeader
        title="Órgãos de Defesa Civil"
        description="Gestão de COMPDEC, REDEC e CEDEC"
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
            Novo Órgão
          </Button>
        </template>
      </PageHeader>

      <!-- Cards de Estatísticas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" v-if="statistics">
        <StatCard
          title="Total de Órgãos"
          :value="statistics.total"
          variant="info"
          :icon="BuildingOfficeIcon"
        />
        <StatCard
          title="COMPDECs"
          :value="statistics.por_tipo.compdec || 0"
          variant="success"
          :icon="BuildingOfficeIcon"
        />
        <StatCard
          title="REDECs"
          :value="statistics.por_tipo.redec || 0"
          variant="warning"
          :icon="BuildingOfficeIcon"
        />
        <StatCard
          title="Ativos"
          :value="statistics.ativos"
          variant="success"
          :icon="CheckCircleIcon"
        />
      </div>

      <!-- Filtros Padronizados -->
      <OrgaosFiltersSection
        :filters="localFilters"
        :municipalities="filterOptions?.municipalities || []"
        @filter-change="handleFilterChange"
        @filter-reset="handleFilterReset"
      />

      <!-- Tabela de Órgãos -->
      <div class="table-container">
        <div v-if="loading" class="loading-overlay">
          <div class="spinner"></div>
        </div>

        <table class="orgaos-table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Nome</th>
              <th>Tipo</th>
              <th>Município</th>
              <th>Status</th>
              <th>Usuários</th>
              <th class="actions-column">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="orgaos.data.length === 0">
              <td colspan="7" class="empty-state">
                Nenhum órgão encontrado
              </td>
            </tr>
            <tr v-for="orgao in orgaos.data" :key="orgao.id" class="table-row">
              <td>
                <Text variant="mono">{{ orgao.codigo }}</Text>
              </td>
              <td>
                <Text variant="bold">{{ orgao.nome }}</Text>
              </td>
              <td>
                <Badge :color="getTipoBadgeColor(orgao.tipo)">
                  {{ orgao.tipoLabel }}
                </Badge>
              </td>
              <td>
                <Text variant="muted">
                  {{ orgao.municipio?.nome || '—' }}
                </Text>
              </td>
              <td>
                <Badge :color="orgao.statusBadgeColor">
                  {{ orgao.statusLabel }}
                </Badge>
              </td>
              <td>
                <Text variant="muted">{{ orgao.usuarios_count || 0 }}</Text>
              </td>
              <td class="actions-column">
                <div class="flex items-center justify-end">
                  <TableActions
                    :show-view="true"
                    :show-edit="canManage"
                    :show-attachments="false"
                    :show-delete="false"
                    @view="handleView(orgao.id)"
                    @edit="handleEdit(orgao.id)"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <Pagination
        v-if="orgaos.data.length > 0"
        :current-page="orgaos.current_page"
        :last-page="orgaos.last_page"
        :total="orgaos.total"
        @page-change="handlePageChange"
      />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import OrgaosFiltersSection from '@/Components/Organisms/Compdec/OrgaosFiltersSection.vue';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';

const props = defineProps({
  orgaos: {
    type: Object,
    required: true,
  },
  statistics: {
    type: Object,
    default: () => ({}),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  canManage: {
    type: Boolean,
    default: false,
  },
});

const loading = ref(false);
const localFilters = ref({ ...props.filters });
let debounceTimer = null;

const hasActiveFilters = computed(() => {
  return Object.values(localFilters.value).some(v => v !== '' && v !== null);
});

const getTipoBadgeColor = (tipo) => {
  const colors = {
    compdec: 'blue',
    redec: 'yellow',
    cedec: 'purple',
  };
  return colors[tipo] || 'gray';
};

const applyFilters = () => {
  loading.value = true;
  router.get(route('compdec.index'), localFilters.value, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    },
  });
};

const debouncedSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    applyFilters();
  }, 500);
};

const clearFilters = () => {
  localFilters.value = { tipo: '', status: '', search: '' };
  applyFilters();
};

const handleFilterChange = (newFilters) => {
  localFilters.value = { ...newFilters };
  applyFilters();
};

const handleFilterReset = () => {
  clearFilters();
};

const handlePageChange = (page) => {
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
};

const handleCreate = () => {
  router.visit(route('compdec.create'));
};

const handleView = (id) => {
  router.visit(route('compdec.show', id));
};

const handleEdit = (id) => {
  router.visit(route('compdec.edit', id));
};
</script>

<style scoped>
.orgaos-index {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
  /* Padding removed for global alignment */
}

.filters-section {
  @apply bg-white dark:bg-slate-800;
  margin-bottom: 2rem;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.table-container {
  @apply bg-white dark:bg-slate-800;
  position: relative;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 2rem;
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

.orgaos-table {
  width: 100%;
  border-collapse: collapse;
}

.orgaos-table thead {
  @apply bg-gray-50 dark:bg-slate-700;
  border-bottom: 2px solid #e5e7eb;
}

.orgaos-table th {
  @apply text-gray-700 dark:text-gray-300;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.orgaos-table td {
  @apply border-b border-gray-200 dark:border-slate-700;
  padding: 1rem;
}

.table-row {
  @apply hover:bg-gray-50 dark:hover:bg-slate-700/50;
  transition: background-color 0.15s ease;
}

.empty-state {
  @apply text-gray-500 dark:text-gray-400;
  text-align: center;
  padding: 3rem;
}

.actions-column {
  width: 180px;
  text-align: right;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}
</style>
