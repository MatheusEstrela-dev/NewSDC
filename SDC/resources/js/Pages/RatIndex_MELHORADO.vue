<template>
  <div>
    <Head title="Gestão de RAT" />

    <div class="container mx-auto px-4 py-8">

      <!-- Alertas Flash -->
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="flashMessage"
          :class="[
            'flex items-center gap-3 mb-6 px-5 py-4 rounded-xl shadow-md border text-sm font-medium',
            flashType === 'success'
              ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-700 text-green-800 dark:text-green-200'
              : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200'
          ]"
        >
          <!-- Ícone -->
          <svg v-if="flashType === 'success'" class="w-5 h-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else class="w-5 h-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="flex-1">{{ flashMessage }}</span>
          <button @click="flashMessage = null" class="ml-2 opacity-60 hover:opacity-100 transition-opacity">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </Transition>

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

    <!-- Modal de Confirmação de Exclusão -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="deletingRatId"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
          @click.self="deletingRatId = null"
        >
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Excluir RAT</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Tem certeza que deseja excluir este RAT? Esta ação não pode ser desfeita.
                </p>
              </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button
                @click="deletingRatId = null"
                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="confirmDelete"
                class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors"
              >
                Sim, excluir
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();
const page = usePage();

// ─── Flash Messages ────────────────────────────────────────────────────────
const flashMessage = ref(null);
const flashType    = ref('success');
let   flashTimer   = null;

function showFlash(message, type = 'success') {
  if (flashTimer) clearTimeout(flashTimer);
  flashMessage.value = message;
  flashType.value    = type;
  flashTimer = setTimeout(() => { flashMessage.value = null; }, 4000);
}

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) showFlash(flash.success, 'success');
    if (flash?.error)   showFlash(flash.error,   'error');
  },
  { immediate: true, deep: true }
);

const props = defineProps({
  statistics:    { type: Object, default: () => ({ total: 0, hoje: 0, esteMes: 0, esteAno: 0 }) },
  rats:          { type: [Array, Object], default: () => [] },
  filters:       { type: Object, default: () => ({}) },
  pagination:    { type: Object, default: null },
  municipalities:{ type: Array, default: () => [] },
  cobradeTypes:  { type: Array, default: () => [] },
  years:         { type: Array, default: () => [] },
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

// ─── Delete ─────────────────────────────────────────────────────────────────
const deletingRatId = ref(null);

function deleteRat(ratId) {
  deletingRatId.value = ratId;
}

function confirmDelete() {
  if (!deletingRatId.value) return;
  router.delete(route('compdec.rat.destroy', deletingRatId.value), {
    onSuccess: () => {
      deletingRatId.value = null;
      applyFilters();
    },
    onError: () => {
      deletingRatId.value = null;
      showFlash('Erro ao excluir o RAT. Tente novamente.', 'error');
    },
  });
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
  // Dados carregados via Inertia props
});
</script>

<style scoped>
/* Estilos customizados se necessário */
</style>
