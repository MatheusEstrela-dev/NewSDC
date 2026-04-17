<template>
  <div>
    <Head title="Gestão de RAT" />

    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Gestão de RAT
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Visualize e gerencie todos os Registros de Atendimento Técnico
          </p>
        </div>
        <button
          v-if="can('rat.create')"
          @click="() => router.visit(route('compdec.rat.create'))"
          class="px-6 py-3 rounded-lg bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold hover:from-blue-500 hover:to-blue-400 transition-all shadow-lg"
        >
          + Novo RAT
        </button>
      </div>

      <!-- Estatísticas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total de RATs</p>
          <p class="text-4xl font-bold text-blue-600 mt-2">{{ statistics.total }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Hoje</p>
          <p class="text-4xl font-bold text-green-600 mt-2">{{ statistics.hoje }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Este Mês</p>
          <p class="text-4xl font-bold text-purple-600 mt-2">{{ statistics.esteMes }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Este Ano</p>
          <p class="text-4xl font-bold text-orange-600 mt-2">{{ statistics.esteAno }}</p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filtros de Pesquisa</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Status
            </label>
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="">Todos</option>
              <option value="rascunho">Rascunho</option>
              <option value="em_andamento">Em Andamento</option>
              <option value="finalizado">Finalizado</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>

          <!-- Período -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Período
            </label>
            <input
              v-model="filters.periodo"
              type="date"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            />
          </div>

          <!-- Município -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Município
            </label>
            <select
              v-model="filters.municipio"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="">Todos</option>
              <option v-for="m in municipalities" :key="m.id" :value="m.id">
                {{ m.name }}
              </option>
            </select>
          </div>

          <!-- Tipo COBRADE -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tipo COBRADE
            </label>
            <select
              v-model="filters.cobrade"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="">Todos</option>
              <option v-for="c in cobradeTypes" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Botões -->
        <div class="flex gap-2 mt-4">
          <button
            @click="applyFilters"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors"
          >
            Filtrar
          </button>
          <button
            @click="clearFilters"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Limpar Filtros
          </button>
          <button
            v-if="can('rat.export')"
            @click="exportData"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Exportar CSV
          </button>
        </div>
      </div>

      <!-- Tabela de RATs -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-block">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mt-4">Carregando dados...</p>
        </div>

        <table v-else class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Protocolo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Município
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Data
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="rat in rats" :key="rat.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                {{ rat.protocolo }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                {{ rat.municipio }}
              </td>
              <td class="px-6 py-4 text-sm">
                <span
                  :class="{
                    'px-3 py-1 rounded-full text-xs font-semibold': true,
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': rat.status === 'rascunho',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': rat.status === 'em_andamento',
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': rat.status === 'finalizado',
                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': rat.status === 'cancelado',
                  }"
                >
                  {{ formatStatus(rat.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                {{ formatDate(rat.created_at) }}
              </td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button
                  @click="() => router.visit(route('compdec.rat.show', rat.id))"
                  class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200 font-medium"
                >
                  Visualizar
                </button>
                <button
                  v-if="can('rat.update')"
                  @click="() => router.visit(route('compdec.rat.edit', rat.id))"
                  class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200 font-medium"
                >
                  Editar
                </button>
                <button
                  v-if="can('rat.delete')"
                  @click="() => deleteRat(rat.id)"
                  class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 font-medium"
                >
                  Excluir
                </button>
              </td>
            </tr>

            <tr v-if="rats.length === 0" class="h-24">
              <td colspan="5" class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">
                Nenhum RAT encontrado com os filtros selecionados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} RATs
        </div>
        <div class="space-x-2">
          <button
            v-for="link in pagination.links"
            :key="link.label"
            @click="goToPage(link.url)"
            :disabled="!link.url"
            :class="{
              'px-3 py-2 rounded border': true,
              'border-blue-600 bg-blue-600 text-white': link.active,
              'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700': !link.active && link.url,
              'border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-600 cursor-not-allowed': !link.url,
            }"
            v-html="link.label"
          ></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
  statistics: { type: Object, default: () => ({ total: 0, hoje: 0, esteMes: 0, esteAno: 0 }) },
  rats: { type: [Array, Object], default: () => [] },
  filters: { type: Object, default: () => ({}) },
  pagination: { type: Object, default: null },
  municipalities: { type: Array, default: () => [] },
  cobradeTypes: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
});

const loading = ref(false);

const filters = ref({
  status: props.filters.status || '',
  periodo: props.filters.periodo || '',
  municipio: props.filters.municipio || '',
  cobrade: props.filters.cobrade || '',
});

const rats = computed(() => {
  if (Array.isArray(props.rats)) return props.rats;
  return props.rats?.data || [];
});

const pagination = computed(() => {
  if (props.pagination) return props.pagination;
  if (props.rats && !Array.isArray(props.rats)) {
    return {
      current_page: props.rats.current_page,
      last_page: props.rats.last_page,
      per_page: props.rats.per_page,
      total: props.rats.total,
      from: (props.rats.current_page - 1) * props.rats.per_page + 1,
      to: Math.min(props.rats.current_page * props.rats.per_page, props.rats.total),
      links: props.rats.links,
    };
  }
  return null;
});

const statistics = computed(() => props.statistics);
const municipalities = computed(() => props.municipalities);
const cobradeTypes = computed(() => props.cobradeTypes);

function applyFilters() {
  loading.value = true;
  router.get(route('compdec.rat.index'), filters.value, {
    preserveScroll: true,
    onFinish: () => (loading.value = false),
  });
}

function clearFilters() {
  filters.value = { status: '', periodo: '', municipio: '', cobrade: '' };
  applyFilters();
}

function exportData() {
  window.location.href = route('compdec.rat.export', { filters: JSON.stringify(filters.value) });
}

function goToPage(url) {
  if (url) {
    loading.value = true;
    router.visit(url, { preserveScroll: true, onFinish: () => (loading.value = false) });
  }
}

function deleteRat(ratId) {
  if (confirm('Tem certeza que deseja excluir este RAT?')) {
    router.delete(route('compdec.rat.destroy', ratId), {
      onSuccess: () => {
        applyFilters();
      },
    });
  }
}

function formatStatus(status) {
  const statusMap = {
    rascunho: 'Rascunho',
    em_andamento: 'Em Andamento',
    finalizado: 'Finalizado',
    cancelado: 'Cancelado',
  };
  return statusMap[status] || status;
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('pt-BR');
}

onMounted(() => {
  // Dados carregados do servidor via props
});
</script>

<style scoped>
/* Estilos customizados se necessário */
</style>
