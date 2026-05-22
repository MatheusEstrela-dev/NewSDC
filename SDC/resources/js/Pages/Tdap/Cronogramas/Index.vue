<template>
  <Head title="TDAP — Cronogramas" />
  <div class="p-6 space-y-6">
    <PageHeader variant="gradient"
      title="Cronogramas de Fornecimento"
      description="Ordens operacionais de entrega de água potável"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.cronogramas.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Novo Cronograma</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <StatCard title="Total" :value="estatisticas.total ?? 0" :icon="ClipboardDocumentListIcon" variant="info" />
      <StatCard title="Ativos" :value="estatisticas.ativos ?? 0" :icon="CheckCircleIcon" variant="success" />
      <StatCard title="Rascunhos" :value="estatisticas.rascunhos ?? 0" :icon="DocumentTextIcon" variant="warning" />
      <StatCard title="Encerrados" :value="estatisticas.encerrados ?? 0" :icon="CheckIcon" variant="info" />
      <StatCard title="Volume ativo (m³)" :value="Number(estatisticas.volume_ativo_m3 || 0).toLocaleString('pt-BR', {minimumFractionDigits:0,maximumFractionDigits:0})" :icon="CubeIcon" variant="info" :format-number="false" />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="false">
      <FilterField
        label="Buscar"
        type="text"
        :model-value="filtroSearch"
        placeholder="Número ou empenho"
        @update:model-value="filtroSearch = $event"
      />
      <FilterField
        label="Estado"
        type="select"
        :model-value="filtroEstado"
        :options="estadoOptions"
        @update:model-value="filtroEstado = $event"
      />
      <FilterField
        label="Ata"
        type="select"
        :model-value="filtroAta"
        :options="ataOptions"
        @update:model-value="filtroAta = $event"
      />
      <div class="md:col-span-2 lg:col-span-3 flex justify-end items-end pt-1">
        <FilterActions @search="aplicarFiltros" @clear="limparFiltros" />
      </div>
    </FilterSection>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Número</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Vigência</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ata / Lote</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Município / Prestador</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Volume (m³)</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Caminhões</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Estado</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="c in cronogramas.data" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono">
              <Link :href="route('tdap.cronogramas.show', c.id)" class="text-blue-600 hover:text-blue-800 font-semibold">{{ c.numero }}</Link>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ fmtDate(c.dt_inicio) }} — {{ fmtDate(c.dt_final) }}</td>
            <td class="px-4 py-3 text-sm">
              <p class="font-mono">{{ c.ata_numero }}</p>
              <p class="text-xs text-slate-500 font-mono">{{ c.lote_numero }}</p>
            </td>
            <td class="px-4 py-3 text-sm">
              <p>{{ c.municipio_nome }}<span v-if="c.municipio_uf" class="text-slate-400">/{{ c.municipio_uf }}</span></p>
              <p class="text-xs text-slate-500">{{ c.prestador_nome }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-right font-mono">{{ Number(c.volume_contratado_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
            <td class="px-4 py-3 text-sm text-center">{{ c.caminhoes_count }}</td>
            <td class="px-4 py-3 text-sm">
              <EstadoBadge :estado="c.estado" />
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-2">
              <Link :href="route('tdap.cronogramas.show', c.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
              <Link v-if="canEdit && c.estado === 'rascunho'" :href="route('tdap.cronogramas.edit', c.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
            </td>
          </tr>
          <tr v-if="cronogramas.data.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-slate-400">Nenhum cronograma cadastrado.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="cronogramas.meta && cronogramas.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">
          Página {{ cronogramas.meta.current_page }} de {{ cronogramas.meta.last_page }} ({{ cronogramas.meta.total }} registros)
        </p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in cronogramas.meta.links || []"
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
import EstadoBadge from '@/Components/Organisms/Tdap/EstadoBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import { computed } from 'vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  cronogramas:  { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, rascunhos: 0, encerrados: 0, volume_ativo_m3: 0 }) },
  atas:         { type: Array, default: () => [] },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canAtivar:    { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroSearch = ref(props.filtros.search ?? '');
const filtroEstado = ref(props.filtros.estado ?? '');
const filtroAta    = ref(props.filtros.ata_id ?? '');

const estadoOptions = [
  { value: '', label: 'Todos' },
  { value: 'rascunho', label: 'Rascunho' },
  { value: 'ativo', label: 'Ativo' },
  { value: 'encerrado', label: 'Encerrado' },
];
const ataOptions = computed(() => [
  { value: '', label: 'Todas' },
  ...props.atas.map(a => ({ value: a.id, label: a.numero })),
]);

function aplicarFiltros() {
  router.get(route('tdap.cronogramas.index'), {
    search: filtroSearch.value || undefined,
    estado: filtroEstado.value || undefined,
    ata_id: filtroAta.value || undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  filtroSearch.value = '';
  filtroEstado.value = '';
  filtroAta.value = '';
  router.get(route('tdap.cronogramas.index'), {}, { preserveState: false });
}

function fmtDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
