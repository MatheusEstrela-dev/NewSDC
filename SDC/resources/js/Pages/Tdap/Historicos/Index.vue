<template>
  <Head title="TDAP — Histórico" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Histórico TDAP"
      description="Auditoria cronológica de eventos de negócio"
      :icon="ClockIcon"
    >
      <template #actions>
        <ActionButton
          action="export"
          :allowed="true"
          variant="success"
          label="Exportar"
          @click="openExportModal"
        />
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Total de eventos</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ estatisticas.total }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Este mês</p>
        <p class="text-2xl font-semibold text-blue-600">{{ estatisticas.mes_atual }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-sm text-slate-500">Cronogramas envolvidos</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ estatisticas.cronogramas_envolvidos }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <select v-model="filtroEntityType" class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" @change="aplicarFiltros">
          <option value="">Tipo de entidade</option>
          <option value="cronograma">Cronograma</option>
          <option value="viagem">Viagem</option>
          <option value="vistoria">Vistoria</option>
          <option value="crono_caminhao">Caminhão alocado</option>
        </select>
        <input v-model="filtroTipo" type="text" placeholder="tipo_evento (ex: cronograma.ativado)" class="border-slate-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm md:col-span-2" @keyup.enter="aplicarFiltros" />
        <DatePicker v-model="filtroDe" placeholder="Data inicial" @update:model-value="aplicarFiltros" />
        <DatePicker v-model="filtroAte" placeholder="Data final" @update:model-value="aplicarFiltros" />
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Quando</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Tipo</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Entidade</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Mensagem</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Usuário</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="h in historicos.data" :key="h.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300 text-xs">{{ fmtDateTime(h.data_evento) }}</td>
            <td class="px-4 py-3">
              <span :class="badgeTipo(h.tipo_evento)" class="px-2 py-0.5 rounded text-xs font-mono">{{ h.tipo_evento }}</span>
            </td>
            <td class="px-4 py-3 text-xs font-mono">{{ h.entity_type }}#{{ h.entity_id }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ h.obs || '—' }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ h.user?.name || 'Sistema' }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <ActionButton
                  action="view"
                  module="tdap"
                  resource="historicos"
                  :allowed="true"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Ver detalhes do evento"
                  @click="router.visit(route('tdap.historicos.show', h.id))"
                />
              </div>
            </td>
          </tr>
          <tr v-if="historicos.data.length === 0">
            <td colspan="6" class="px-4 py-12 text-center text-slate-400">Nenhum evento registrado.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="historicos.meta && historicos.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">Página {{ historicos.meta.current_page }} de {{ historicos.meta.last_page }} ({{ historicos.meta.total }} registros)</p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in historicos.meta.links || []"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1 text-sm rounded border"
            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-300 text-slate-700 dark:text-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
          />
        </div>
      </div>
    </div>

    <ExportCsvModal
      :show="showExportModal"
      module-name="Historicos"
      @close="closeExportModal"
      @export="onExport"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import { useExport } from '@/composables/data/useExport';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  historicos:   { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total:0, mes_atual:0, cronogramas_envolvidos:0 }) },
  filtros:      { type: Object, default: () => ({}) },
});

const filtroEntityType = ref(props.filtros.entity_type ?? '');
const filtroTipo       = ref(props.filtros.tipo_evento ?? '');
const filtroDe         = ref(props.filtros.de ?? '');
const filtroAte        = ref(props.filtros.ate ?? '');

function aplicarFiltros() {
  router.get(route('tdap.historicos.index'), {
    entity_type: filtroEntityType.value || undefined,
    tipo_evento: filtroTipo.value || undefined,
    de:          filtroDe.value || undefined,
    ate:         filtroAte.value || undefined,
  }, { preserveState: true, replace: true });
}

// Exportacao CSV (mesmo padrao do Cronograma)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.historicos.export');

function onExport(params) {
  handleExport(params, {
    entity_type: filtroEntityType.value || undefined,
    tipo_evento: filtroTipo.value || undefined,
    de:          filtroDe.value || undefined,
    ate:         filtroAte.value || undefined,
  });
}

function badgeTipo(tipo) {
  if (!tipo) return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
  if (tipo.includes('ativad') || tipo.includes('aprov')) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
  if (tipo.includes('encerrad') || tipo.includes('reprov') || tipo.includes('rejeit')) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
  if (tipo.includes('criad') || tipo.includes('registrad')) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
  if (tipo.includes('prorrog')) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
}

function fmtDateTime(d) {
  if (!d) return '';
  return new Date(d).toLocaleString('pt-BR');
}
</script>
