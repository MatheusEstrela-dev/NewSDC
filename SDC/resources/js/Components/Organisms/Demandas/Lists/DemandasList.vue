<template>
  <div class="demandas-list">
    <!-- Filtros -->
    <CardBase variant="default" padding="lg" class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <Heading :level="4" color="default">Filtros</Heading>
        <button
          v-if="hasActiveFilters"
          @click="handleClearFilters"
          class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors"
        >
          Limpar filtros
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Busca -->
        <div class="lg:col-span-2">
          <input
            v-model="localFilters.search"
            @input="handleFilterChange"
            type="text"
            placeholder="Buscar por título, código ou descrição..."
            class="w-full px-4 py-2 rounded-lg
                   bg-white dark:bg-slate-900
                   border border-slate-300 dark:border-slate-700
                   text-slate-900 dark:text-slate-100
                   placeholder-slate-400 dark:placeholder-slate-500
                   focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all text-sm"
          />
        </div>

        <!-- Tipo -->
        <div>
          <select
            v-model="localFilters.tipo"
            @change="handleFilterChange"
            class="w-full px-4 py-2 rounded-lg
                   bg-white dark:bg-slate-900
                   border border-slate-300 dark:border-slate-700
                   text-slate-900 dark:text-slate-100
                   focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all text-sm"
          >
            <option value="">Todos os tipos</option>
            <option value="tarefa">Tarefa</option>
            <option value="bug">Bug</option>
            <option value="melhoria">Melhoria</option>
            <option value="suporte">Suporte</option>
          </select>
        </div>

        <!-- Prioridade -->
        <div>
          <select
            v-model="localFilters.prioridade"
            @change="handleFilterChange"
            class="w-full px-4 py-2 rounded-lg
                   bg-white dark:bg-slate-900
                   border border-slate-300 dark:border-slate-700
                   text-slate-900 dark:text-slate-100
                   focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all text-sm"
          >
            <option value="">Todas prioridades</option>
            <option value="baixa">Baixa</option>
            <option value="media">Média</option>
            <option value="alta">Alta</option>
            <option value="critica">Crítica</option>
          </select>
        </div>

        <!-- Status -->
        <div>
          <select
            v-model="localFilters.status"
            @change="handleFilterChange"
            class="w-full px-4 py-2 rounded-lg
                   bg-white dark:bg-slate-900
                   border border-slate-300 dark:border-slate-700
                   text-slate-900 dark:text-slate-100
                   focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all text-sm"
          >
            <option value="">Todos os status</option>
            <option value="aberta">Aberta</option>
            <option value="em_andamento">Em Andamento</option>
            <option value="concluida">Concluída</option>
          </select>
        </div>
      </div>
    </CardBase>

    <!-- Lista de Demandas -->
    <div v-if="demandas.length > 0" class="space-y-4">
      <CardBase
        v-for="demanda in demandas"
        :key="demanda.id"
        variant="default"
        padding="lg"
        class="hover:border-red-500/30 dark:hover:border-red-500/30 hover:border-red-300 transition-all cursor-pointer"
        @click="handleDemandaClick(demanda)"
      >
        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
          <!-- Conteúdo Principal -->
          <div class="flex-1">
            <div class="flex items-start gap-3 mb-3">
              <div class="flex-shrink-0 mt-1">
                <component :is="getTipoIcon(demanda.tipo)" class="w-5 h-5" :class="getTipoColor(demanda.tipo)" />
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <Text size="xs" color="muted" weight="medium">{{ demanda.codigo }}</Text>
                  <span :class="getPrioridadeBadgeClass(demanda.prioridade)">
                    {{ getPrioridadeLabel(demanda.prioridade) }}
                  </span>
                </div>
                <Heading :level="4" color="default" class="mb-2">{{ demanda.titulo }}</Heading>
                <Text size="sm" color="muted" class="line-clamp-2">
                  {{ demanda.descricao }}
                </Text>
              </div>
            </div>

            <!-- Tags -->
            <div v-if="demanda.tags && demanda.tags.length > 0" class="flex flex-wrap gap-2 mb-3">
              <span
                v-for="tag in demanda.tags"
                :key="tag"
                class="px-2 py-1 rounded-md bg-slate-700/50 dark:bg-slate-700/50 bg-slate-200 text-slate-300 dark:text-slate-300 text-slate-700 text-xs"
              >
                #{{ tag }}
              </span>
            </div>

            <!-- Metadados -->
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 dark:text-slate-400 text-slate-600">
              <div v-if="demanda.responsavel_nome" class="flex items-center gap-1">
                <UserIcon class="w-4 h-4" />
                <span>{{ demanda.responsavel_nome }}</span>
              </div>
              <div v-else class="flex items-center gap-1">
                <UserIcon class="w-4 h-4" />
                <span>Não atribuído</span>
              </div>
              <div v-if="demanda.prazo" class="flex items-center gap-1">
                <CalendarIcon class="w-4 h-4" />
                <span>{{ formatDate(demanda.prazo) }}</span>
              </div>
              <div class="flex items-center gap-1">
                <ClockIcon class="w-4 h-4" />
                <span>{{ formatRelativeDate(demanda.created_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Status -->
          <div class="flex-shrink-0">
            <span :class="getStatusBadgeClass(demanda.status)">
              {{ getStatusLabel(demanda.status) }}
            </span>
          </div>
        </div>
      </CardBase>
    </div>

    <!-- Empty State -->
    <CardBase
      v-else
      variant="default"
      padding="xl"
      class="text-center"
    >
      <div class="py-12">
        <ClipboardDocumentListIcon class="w-16 h-16 text-slate-600 dark:text-slate-600 text-slate-400 mx-auto mb-4" />
        <Heading :level="4" color="muted" class="mb-2">Nenhuma demanda encontrada</Heading>
        <Text size="sm" color="muted">
          {{ hasActiveFilters ? 'Tente ajustar os filtros' : 'Crie sua primeira demanda para começar' }}
        </Text>
      </div>
    </CardBase>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import WrenchScrewdriverIcon from '@/Components/Icons/WrenchScrewdriverIcon.vue';
import LifebuoyIcon from '@/Components/Icons/LifebuoyIcon.vue';
import UserIcon from '@/Components/Icons/UserIcon.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';

const props = defineProps({
  demandas: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  getTipoLabel: {
    type: Function,
    required: true,
  },
  getPrioridadeLabel: {
    type: Function,
    required: true,
  },
  getStatusLabel: {
    type: Function,
    required: true,
  },
});

const emit = defineEmits(['filter-change', 'clear-filters', 'demanda-click']);

const localFilters = ref({ ...props.filters });

watch(
  () => props.filters,
  (newFilters) => {
    localFilters.value = { ...newFilters };
  },
  { deep: true }
);

const hasActiveFilters = computed(() => {
  return Object.values(localFilters.value).some(value => value !== '');
});

const handleFilterChange = () => {
  emit('filter-change', localFilters.value);
};

const handleClearFilters = () => {
  localFilters.value = {
    search: '',
    tipo: '',
    prioridade: '',
    status: '',
    responsavel_id: '',
  };
  emit('clear-filters');
};

const handleDemandaClick = (demanda) => {
  emit('demanda-click', demanda);
};

// Helpers
const getTipoIcon = (tipo) => {
  const icons = {
    tarefa: CheckBadgeIcon,
    bug: ExclamationTriangleIcon,
    melhoria: WrenchScrewdriverIcon,
    suporte: LifebuoyIcon,
  };
  return icons[tipo] || CheckBadgeIcon;
};

const getTipoColor = (tipo) => {
  const colors = {
    tarefa: 'text-blue-400',
    bug: 'text-red-400',
    melhoria: 'text-purple-400',
    suporte: 'text-green-400',
  };
  return colors[tipo] || 'text-slate-400';
};

const getPrioridadeBadgeClass = (prioridade) => {
  const classes = {
    baixa: 'px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-500/15 text-gray-700 dark:text-gray-300 text-xs font-medium',
    media: 'px-2 py-0.5 rounded-md bg-yellow-100 dark:bg-yellow-500/15 text-yellow-700 dark:text-yellow-300 text-xs font-medium',
    alta: 'px-2 py-0.5 rounded-md bg-orange-100 dark:bg-orange-500/15 text-orange-700 dark:text-orange-300 text-xs font-medium',
    critica: 'px-2 py-0.5 rounded-md bg-red-100 dark:bg-red-500/15 text-red-700 dark:text-red-300 text-xs font-medium',
  };
  return classes[prioridade] || classes.media;
};

const getStatusBadgeClass = (status) => {
  const classes = {
    aberta: 'px-3 py-1.5 rounded-lg bg-cyan-100 dark:bg-cyan-500/15 text-cyan-700 dark:text-cyan-300 text-sm font-medium border border-cyan-300 dark:border-cyan-500/30',
    em_andamento: 'px-3 py-1.5 rounded-lg bg-yellow-100 dark:bg-yellow-500/15 text-yellow-700 dark:text-yellow-300 text-sm font-medium border border-yellow-300 dark:border-yellow-500/30',
    concluida: 'px-3 py-1.5 rounded-lg bg-green-100 dark:bg-green-500/15 text-green-700 dark:text-green-300 text-sm font-medium border border-green-300 dark:border-green-500/30',
    cancelada: 'px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-500/15 text-gray-700 dark:text-gray-300 text-sm font-medium border border-gray-300 dark:border-gray-500/30',
  };
  return classes[status] || classes.aberta;
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-BR');
};

const formatRelativeDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays === 0) return 'Hoje';
  if (diffDays === 1) return 'Ontem';
  if (diffDays < 7) return `${diffDays} dias atrás`;
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} semanas atrás`;
  return formatDate(dateString);
};
</script>
