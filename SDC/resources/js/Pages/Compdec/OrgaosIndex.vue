<template>
  <AuthenticatedLayout title="Órgãos - COMPDEC/REDEC/CEDEC">
    <div class="orgaos-index">
      <!-- Header com Estatísticas -->
      <div class="header-section">
        <div class="header-title">
          <Heading level="1">Órgãos de Defesa Civil</Heading>
          <Text variant="muted">Gestão de COMPDEC, REDEC e CEDEC</Text>
        </div>

        <Button
          v-if="canManage"
          variant="primary"
          @click="handleCreate"
        >
          <PlusIcon class="w-5 h-5" />
          Novo Órgão
        </Button>
      </div>

      <!-- Cards de Estatísticas -->
      <div class="stats-grid" v-if="statistics">
        <StatCard
          title="Total de Órgãos"
          :value="statistics.total"
          icon="building"
          color="blue"
        />
        <StatCard
          title="COMPDECs"
          :value="statistics.por_tipo.compdec || 0"
          icon="building"
          color="green"
        />
        <StatCard
          title="REDECs"
          :value="statistics.por_tipo.redec || 0"
          icon="building"
          color="yellow"
        />
        <StatCard
          title="Ativos"
          :value="statistics.ativos"
          icon="check"
          color="green"
        />
      </div>

      <!-- Filtros -->
      <div class="filters-section">
        <div class="filters-grid">
          <SelectInput
            v-model="localFilters.tipo"
            label="Tipo de Órgão"
            :options="[
              { value: '', label: 'Todos' },
              { value: 'compdec', label: 'COMPDEC' },
              { value: 'redec', label: 'REDEC' },
              { value: 'cedec', label: 'CEDEC' }
            ]"
            @update:modelValue="applyFilters"
          />

          <SelectInput
            v-model="localFilters.status"
            label="Status"
            :options="[
              { value: '', label: 'Todos' },
              { value: 'ativo', label: 'Ativo' },
              { value: 'inativo', label: 'Inativo' },
              { value: 'em_implantacao', label: 'Em Implantação' },
              { value: 'suspenso', label: 'Suspenso' }
            ]"
            @update:modelValue="applyFilters"
          />

          <TextInput
            v-model="localFilters.search"
            label="Buscar"
            placeholder="Nome ou código..."
            @update:modelValue="debouncedSearch"
          />

          <Button
            v-if="hasActiveFilters"
            variant="outline"
            @click="clearFilters"
          >
            Limpar Filtros
          </Button>
        </div>
      </div>

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
                <div class="action-buttons">
                  <Button
                    variant="ghost"
                    size="sm"
                    @click="handleView(orgao.id)"
                    title="Ver detalhes"
                  >
                    Ver
                  </Button>
                  <Button
                    v-if="canManage"
                    variant="ghost"
                    size="sm"
                    @click="handleEdit(orgao.id)"
                    title="Editar"
                  >
                    Editar
                  </Button>
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
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

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
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.header-title {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
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
