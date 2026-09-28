<template>
  <div class="space-y-6 pb-8">
    <PageHeader title="Painel de demandas" description="Volume e andamento dos chamados" :icon-image="moduleIcon('demandas')" variant="gradient" :espaco-inferior="false">
      <template #actions>
        <Button v-if="pode.exportar" variant="secondary" size="md" :icon="DownloadIcon" icon-position="left" @click="$emit('exportar')">Exportar quantitativo</Button>
      </template>
    </PageHeader>

    <DemandasStatisticsCards :estatisticas="estatisticas" @filtrar="(e) => $emit('filtrar-etapa', e)" />

    <ListContainer title="Últimos 10 meses" :icon="ChartIcon">
      <div class="h-72 min-w-0">
        <LazyChart type="bar" height="100%" :options="opcoesGrafico" :series="series" />
      </div>
    </ListContainer>

    <ListContainer title="Mais recentes" :icon="DocumentTextIcon" :count="recentes.length">
      <ul class="divide-y divide-slate-200 dark:divide-slate-700/50">
        <li v-for="d in recentes" :key="d.id" class="flex cursor-pointer flex-wrap items-center justify-between gap-2 py-3" @click="$emit('abrir', d.id)">
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ d.titulo }}</p>
            <p class="text-xs text-slate-500">{{ d.protocolo }} · {{ d.solicitante ?? '—' }} · {{ formatarDataHora(d.created_at) }}</p>
          </div>
          <div class="flex gap-2">
            <DemandaStatusBadge :etapa="d.etapa" :label="d.etapa_label" />
            <DemandaPrioridadeBadge :prioridade="d.prioridade_simples" :label="d.prioridade_label" />
          </div>
        </li>
        <li v-if="!recentes.length" class="py-6 text-center text-sm text-slate-500">Nenhuma demanda ainda.</li>
      </ul>
    </ListContainer>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ChartIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import DemandasStatisticsCards from '@/Components/Organisms/Demandas/Statistics/DemandasStatisticsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { formatarDataHora } from '@/Support/demandasFormat';

const props = defineProps({
  estatisticas: { type: Object, required: true },
  serie: { type: Array, required: true },
  recentes: { type: Array, default: () => [] },
  pode: { type: Object, required: true },
});
defineEmits(['exportar', 'abrir', 'filtrar-etapa']);

const MES = new Intl.DateTimeFormat('pt-BR', { month: 'short', year: '2-digit' });
const series = computed(() => [
  { name: 'Abertas', data: props.serie.map((s) => s.abertas) },
  { name: 'Resolvidas', data: props.serie.map((s) => s.resolvidas) },
]);
const opcoesGrafico = computed(() => ({
  chart: { toolbar: { show: false }, background: 'transparent' },
  xaxis: { categories: props.serie.map((s) => MES.format(new Date(`${s.mes}-01T12:00:00`))) },
  colors: ['#f97316', '#10b981'],
  dataLabels: { enabled: false },
  legend: { position: 'top' },
  theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
}));
</script>
