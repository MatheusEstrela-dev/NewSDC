<template>
  <div class="pb-6">
    <Head title="Demandas" />

    <PageHeader 
      title="Demandas" 
      description="Gerenciamento central de tarefas e chamados" 
      :icon-image="moduleIcon('demandas')" 
      variant="gradient"
    >
      <template #actions>
        <Button v-if="canExport" variant="success" :icon="ArrowDownTrayIcon" @click="handleExport">
          <span class="hidden sm:inline">Exportar</span>
        </Button>
        <Button v-if="canCreate" variant="primary" :icon="PlusIcon" @click="showModal = true">
          <span class="hidden sm:inline">Nova Demanda</span>
          <span class="sm:hidden">Nova</span>
        </Button>
      </template>
    </PageHeader>

    <StatCardsGrid :colunas="4">
      <StatCard 
        title="Total" 
        :value="statistics?.total || 0" 
        variant="info" 
        :icon="ClipboardDocumentListIcon" 
        clickable 
        @click="filterByStatus('')" 
      />
      <StatCard 
        title="Abertas" 
        :value="statistics?.abertas || 0" 
        variant="warning" 
        :icon="ExclamationTriangleIcon" 
        clickable 
        @click="filterByStatus('aberta')" 
      />
      <StatCard 
        title="Em Progresso" 
        :value="statistics?.em_andamento || 0" 
        variant="success" 
        :icon="ClockIcon" 
        clickable 
        @click="filterByStatus('em_progresso')" 
      />
      <StatCard 
        title="Concluídas" 
        :value="statistics?.concluidas || 0" 
        variant="danger" 
        :icon="CheckCircleIcon" 
        clickable 
        @click="filterByStatus('resolvida')" 
      />
    </StatCardsGrid>

    <FilterSection title="Filtros de Pesquisa" :columns="4" :default-collapsed="false" class="mb-6">
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Busca Rápida</label>
        <input v-model="form.search" type="text" placeholder="Protocolo, título..." class="form-input w-full rounded-md" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
        <select v-model="form.status" class="form-select w-full rounded-md">
          <option value="">Todos os status</option>
          <option v-for="(label, val) in filterOptions?.status || {}" :key="val" :value="val">{{ label }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo</label>
        <select v-model="form.tipo" class="form-select w-full rounded-md">
          <option value="">Todos os tipos</option>
          <option v-for="(label, val) in filterOptions?.tipos || {}" :key="val" :value="val">{{ label }}</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <Button variant="default" @click="resetFilters" class="w-full">Limpar Filtros</Button>
      </div>
    </FilterSection>

    <ListContainer title="Lista de Demandas" :icon="ClipboardDocumentListIcon">
      <div v-if="!tasks?.data || tasks.data.length === 0" class="p-12 text-center text-slate-500 flex flex-col items-center">
        <ClipboardDocumentListIcon class="h-12 w-12 text-slate-300 mb-3" />
        <p>Nenhuma demanda encontrada com os filtros atuais.</p>
      </div>
      <div v-else class="divide-y divide-slate-200 dark:divide-slate-700">
        <div 
          v-for="demanda in tasks.data" 
          :key="demanda.id" 
          class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4" 
          @click="goToDemanda(demanda.id)"
        >
          <div class="flex gap-4">
            <div class="mt-1 flex-shrink-0">
              <DemandaPrioridadeBadge :prioridade="demanda.prioridade">
                {{ filterOptions?.prioridades?.[demanda.prioridade] || demanda.prioridade }}
              </DemandaPrioridadeBadge>
            </div>
            <div>
              <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="text-xs font-mono font-medium text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">{{ demanda.protocolo }}</span>
                <DemandaStatusBadge :status="demanda.status">
                  {{ filterOptions?.status?.[demanda.status] || demanda.status }}
                </DemandaStatusBadge>
              </div>
              <h4 class="text-base font-semibold text-slate-900 dark:text-white mb-1 leading-tight">{{ demanda.titulo }}</h4>
              <p class="text-sm text-slate-500 line-clamp-1 max-w-2xl">{{ demanda.descricao }}</p>
            </div>
          </div>
          <div class="text-sm text-slate-500 sm:text-right flex-shrink-0 flex sm:flex-col justify-between sm:justify-start">
            <div class="mb-1 font-medium">{{ formatDate(demanda.created_at) }}</div>
            <div class="flex items-center gap-1 sm:justify-end text-xs">
               <span>👤</span> <span class="truncate max-w-[120px]">{{ demanda.solicitante?.name || 'Sistema' }}</span>
            </div>
          </div>
        </div>
      </div>
    </ListContainer>

    <div v-if="tasks?.links && tasks.total > tasks.per_page" class="mt-6 flex justify-center">
      <Pagination :pagination="tasks" />
    </div>

    <NovaDemandaModal 
      :show="showModal" 
      :filter-options="filterOptions"
      @close="showModal = false" 
    />
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
defineOptions({ layout: AuthenticatedLayout });

import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import { moduleIcon } from '@/Support/moduleIcons';

import PageHeader from '@/Components/Organisms/PageHeader.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import NovaDemandaModal from '@/Components/Organisms/Demandas/Modals/NovaDemandaModal.vue';

import { 
  PlusIcon, 
  ArrowDownTrayIcon, 
  ClipboardDocumentListIcon, 
  CheckBadgeIcon, 
  ExclamationTriangleIcon, 
  ClockIcon, 
  CheckCircleIcon 
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
  tasks: Object,
  statistics: Object,
  filters: Object,
  filterOptions: Object,
});

const { can } = usePermissions();
const canCreate = can('demandas.chamados.create');
const canExport = can('demandas.chamados.export');

const showModal = ref(false);

const form = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  tipo: props.filters?.tipo || '',
});

const updateFilters = debounce(() => {
  router.get(route('demandas.index'), form.value, { preserveState: true, preserveScroll: true });
}, 300);

watch(form, () => updateFilters(), { deep: true });

const filterByStatus = (status) => {
  form.value.status = status;
};

const resetFilters = () => {
  form.value = { search: '', status: '', tipo: '' };
};

const goToDemanda = (id) => {
  router.get(route('demandas.show', id));
};

const handleExport = () => {
  window.location.href = route('admin.demandas.export', form.value);
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};
</script>
