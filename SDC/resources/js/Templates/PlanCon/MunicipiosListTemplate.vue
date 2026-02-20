<template>
  <div class="municipios-list-container">
    <PageHeader
      :title="title"
      :description="description"
      :icon="icon"
      variant="gradient"
    >
      <template #actions>
        <div class="flex items-center gap-2 sm:gap-3">
          <Button
            v-if="canExport"
            variant="success"
            size="md"
            :icon="ArrowDownTrayIcon"
            icon-position="left"
            @click="$emit('export')"
          >
            <span class="hidden sm:inline">Exportar</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <div class="mt-6 space-y-4 sm:space-y-6">
      <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
        <div class="relative flex-1 max-w-md">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar municipio..."
            class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-600 bg-slate-800/60 text-slate-200 placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
            @input="handleSearch"
          />
        </div>
        <div class="flex items-center gap-2 text-sm text-slate-400">
          <span>{{ filteredMunicipios.length }} de {{ totalMunicipios }} municipios</span>
        </div>
      </div>

      <div class="hidden sm:block rounded-xl border border-slate-700/50 overflow-hidden">
        <table class="w-full">
          <thead class="bg-slate-800/80">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Municipio
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Codigo IBGE
              </th>
              <th v-if="showSituacao" class="px-4 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Situacao
              </th>
              <th v-if="showDataAtualizacao" class="px-4 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Ultima Atualizacao
              </th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Acoes
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr
              v-for="municipio in paginatedMunicipios"
              :key="municipio.id"
              class="hover:bg-slate-700/30 transition-colors"
            >
              <td class="px-4 py-3">
                <span class="font-medium text-slate-200">{{ municipio.nome }}</span>
              </td>
              <td class="px-4 py-3">
                <span class="text-slate-400 font-mono text-sm">{{ municipio.codigoIbge || '-' }}</span>
              </td>
              <td v-if="showSituacao" class="px-4 py-3">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                  :class="getSituacaoClass(municipio.situacaoPlano)"
                >
                  {{ getSituacaoLabel(municipio.situacaoPlano) }}
                </span>
              </td>
              <td v-if="showDataAtualizacao" class="px-4 py-3">
                <span class="text-slate-400 text-sm">{{ formatDate(municipio.dataUltimaAtualizacao) }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  class="p-2 rounded-lg text-slate-400 hover:text-blue-400 hover:bg-blue-400/10 transition-colors"
                  @click="$emit('view', municipio)"
                >
                  <EyeIcon class="w-5 h-5" />
                </button>
              </td>
            </tr>
            <tr v-if="filteredMunicipios.length === 0">
              <td :colspan="showSituacao && showDataAtualizacao ? 5 : 3" class="px-4 py-8 text-center text-slate-400">
                Nenhum municipio encontrado.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="sm:hidden space-y-3">
        <div
          v-for="municipio in paginatedMunicipios"
          :key="municipio.id"
          class="rounded-xl border border-slate-700/50 bg-slate-800/60 p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <h4 class="font-medium text-slate-200 truncate">{{ municipio.nome }}</h4>
              <p class="text-sm text-slate-400 mt-1">IBGE: {{ municipio.codigoIbge || '-' }}</p>
            </div>
            <span
              v-if="showSituacao"
              class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shrink-0"
              :class="getSituacaoClass(municipio.situacaoPlano)"
            >
              {{ getSituacaoLabel(municipio.situacaoPlano) }}
            </span>
          </div>
          <div v-if="showDataAtualizacao" class="mt-3 pt-3 border-t border-slate-700/50 flex items-center justify-between">
            <span class="text-xs text-slate-500">Atualizado em {{ formatDate(municipio.dataUltimaAtualizacao) }}</span>
            <button
              class="p-2 rounded-lg text-blue-400 hover:bg-blue-400/10 transition-colors"
              @click="$emit('view', municipio)"
            >
              <EyeIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
        <div v-if="filteredMunicipios.length === 0" class="text-center py-8 text-slate-400">
          Nenhum municipio encontrado.
        </div>
      </div>

      <div v-if="totalPages > 1" class="flex items-center justify-center gap-2">
        <button
          :disabled="currentPage === 1"
          class="p-2 rounded-lg border border-slate-600 text-slate-400 hover:text-slate-200 hover:bg-slate-700/50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          @click="currentPage--"
        >
          <ChevronLeftIcon class="w-5 h-5" />
        </button>
        <span class="text-sm text-slate-400 px-3">
          Pagina {{ currentPage }} de {{ totalPages }}
        </span>
        <button
          :disabled="currentPage === totalPages"
          class="p-2 rounded-lg border border-slate-600 text-slate-400 hover:text-slate-200 hover:bg-slate-700/50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          @click="currentPage++"
        >
          <ChevronRightIcon class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import {
  ArrowDownTrayIcon,
  MagnifyingGlassIcon,
  EyeIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, Function],
    required: true,
  },
  municipios: {
    type: Array,
    required: true,
  },
  totalMunicipios: {
    type: Number,
    default: 0,
  },
  showSituacao: {
    type: Boolean,
    default: false,
  },
  showDataAtualizacao: {
    type: Boolean,
    default: false,
  },
  canExport: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['export', 'view']);

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 15;

const filteredMunicipios = computed(() => {
  if (!searchQuery.value) {
    return props.municipios;
  }
  const query = searchQuery.value.toLowerCase();
  return props.municipios.filter(m =>
    m.nome.toLowerCase().includes(query) ||
    (m.codigoIbge && m.codigoIbge.includes(query))
  );
});

const totalPages = computed(() => Math.ceil(filteredMunicipios.value.length / itemsPerPage));

const paginatedMunicipios = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredMunicipios.value.slice(start, end);
});

const handleSearch = () => {
  currentPage.value = 1;
};

const getSituacaoClass = (situacao) => {
  if (situacao === 'regular') {
    return 'bg-emerald-500/15 text-emerald-400';
  }
  return 'bg-orange-500/15 text-orange-400';
};

const getSituacaoLabel = (situacao) => {
  if (situacao === 'regular') {
    return 'Regular';
  }
  return 'Irregular';
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('pt-BR');
};
</script>

<style scoped>
.municipios-list-container {
  @apply min-h-screen;
}
</style>
