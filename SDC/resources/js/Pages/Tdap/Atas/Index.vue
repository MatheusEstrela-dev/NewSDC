<template>
  <Head title="TDAP — Atas" />
  <div class="p-6 space-y-6">
    <PageHeader variant="gradient"
      title="Atas de Registro de Preços"
      description="Contratos-pai que autorizam o fornecimento de água potável"
      :icon="CalendarIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.atas.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Nova Ata</span>
            <span class="sm:hidden">Nova</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <StatCard title="Total" :value="estatisticas.total ?? 0" :icon="DocumentTextIcon" variant="info" />
      <StatCard title="Ativas" :value="estatisticas.ativos ?? 0" :icon="CheckCircleIcon" variant="success" />
      <StatCard title="Vigentes" :value="estatisticas.vigentes ?? 0" :icon="ClockIcon" variant="info" />
      <StatCard title="Encerradas" :value="estatisticas.encerradas ?? 0" :icon="CheckIcon" variant="warning" />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="true">
      <FilterField
        label="Buscar"
        type="text"
        :model-value="filtroSearch"
        placeholder="Por número ou histórico"
        @update:model-value="filtroSearch = $event"
      />
      <FilterField
        label="Status"
        type="select"
        :model-value="filtroAtivo"
        :options="ativoOptions"
        @update:model-value="filtroAtivo = $event"
      />
      <div class="flex items-end pb-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
          <input type="checkbox" v-model="filtroVigente" @change="aplicarFiltros" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          Apenas vigentes
        </label>
      </div>
      <div class="md:col-span-2 lg:col-span-3 flex justify-end items-end pt-1">
        <FilterActions @search="aplicarFiltros" @clear="limparFiltros" />
      </div>
    </FilterSection>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Número</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Vigência</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Lotes</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="a in atas.data" :key="a.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono font-semibold text-slate-900 dark:text-slate-100">
              <Link :href="route('tdap.atas.show', a.id)" class="hover:text-blue-600">{{ a.numero }}</Link>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
              {{ formatDate(a.dt_inicio) }} — {{ formatDate(a.dt_final) }}
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ a.lotes_count }}</td>
            <td class="px-4 py-3 text-sm">
              <span v-if="a.vigente" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">Vigente</span>
              <span v-else-if="a.ativo" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">Ativa</span>
              <span v-else class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">Inativa</span>
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-2">
              <Link :href="route('tdap.atas.show', a.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
              <Link v-if="canEdit" :href="route('tdap.atas.edit', a.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
            </td>
          </tr>
          <tr v-if="atas.data.length === 0">
            <td colspan="5" class="px-4 py-12 text-center text-slate-400">Nenhuma ata cadastrada.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="atas.meta && atas.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">
          Página {{ atas.meta.current_page }} de {{ atas.meta.last_page }} ({{ atas.meta.total }} registros)
        </p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in atas.meta.links || []"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1 text-sm rounded border"
            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-300 text-slate-700 dark:text-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  atas:         { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, vigentes: 0, encerradas: 0 }) },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroSearch = ref(props.filtros.search ?? '');
const filtroAtivo  = ref(props.filtros.ativo ?? '');
const filtroVigente = ref(Boolean(props.filtros.vigente));

const ativoOptions = [
  { value: '', label: 'Todas' },
  { value: '1', label: 'Ativas' },
  { value: '0', label: 'Inativas' },
];

function aplicarFiltros() {
  router.get(route('tdap.atas.index'), {
    search:  filtroSearch.value || undefined,
    ativo:   filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
    vigente: filtroVigente.value ? 1 : undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  filtroSearch.value = '';
  filtroAtivo.value = '';
  filtroVigente.value = false;
  router.get(route('tdap.atas.index'), {}, { preserveState: false });
}

function formatDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
